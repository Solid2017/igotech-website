<?php
/**
 * IgoTech -- API Chatbot
 * POST /api/chatbot.php
 *
 * Moteur hybride :
 *   1. Détection d'intention par mots-clés (rule-based)
 *   2. Si intention non reconnue et Claude API activée → appel curl Anthropic
 *   3. Capture de leads (nom, email, téléphone)
 */
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Méthode non autorisée.');
}

// -- Vérifier que le chatbot est actif -------------------------
if (setting('chatbot_actif', '1') !== '1') {
    json_response(false, 'Chatbot désactivé.');
}

$input   = json_decode(file_get_contents('php://input'), true) ?? [];
$message = sanitize($input['message']    ?? '');
$sessId  = sanitize($input['session_id'] ?? '');
$lang    = in_array($input['lang'] ?? 'fr', ['fr','en']) ? $input['lang'] : 'fr';
$page    = sanitize($input['page']       ?? '');

if (!$message || !$sessId) {
    json_response(false, 'Données manquantes.');
}

// -- Conversation en cours -------------------------------------
$db = db();
$stmt = $db->prepare("SELECT * FROM chatbot_conversations WHERE session_id=? ORDER BY id DESC LIMIT 1");
$stmt->execute([$sessId]);
$conv = $stmt->fetch();

if (!$conv) {
    $db->prepare("INSERT INTO chatbot_conversations (session_id, langue, ip, page_origine) VALUES (?,?,?,?)")
       ->execute([$sessId, $lang, get_ip(), $page]);
    $convId = (int)$db->lastInsertId();
    $conv = ['id' => $convId, 'nb_messages' => 0, 'visiteur_nom' => null, 'visiteur_email' => null];
} else {
    $convId = (int)$conv['id'];
}

// Sauvegarde message utilisateur
$db->prepare("INSERT INTO chatbot_messages (conversation_id, role, contenu) VALUES (?,?,?)")
   ->execute([$convId, 'user', $message]);
$db->prepare("UPDATE chatbot_conversations SET nb_messages=nb_messages+1, updated_at=NOW() WHERE id=?")->execute([$convId]);

// -- Capture de leads ------------------------------------------
$leadCapture = detectLeadInfo($message, $conv);
if ($leadCapture) {
    foreach ($leadCapture as $field => $val) {
        $db->prepare("UPDATE chatbot_conversations SET {$field}=? WHERE id=?")->execute([$val, $convId]);
        $conv[$field] = $val;
    }
    if (!empty($leadCapture['visiteur_email']) || !empty($leadCapture['visiteur_tel'])) {
        $db->prepare("UPDATE chatbot_conversations SET statut='lead' WHERE id=?")->execute([$convId]);
    }
}

// -- Historique (pour contexte AI) ----------------------------
$histStmt = $db->prepare("SELECT role, contenu FROM chatbot_messages WHERE conversation_id=? ORDER BY id DESC LIMIT 10");
$histStmt->execute([$convId]);
$history = array_reverse($histStmt->fetchAll());

// -- Détection d'intention -------------------------------------
$intent   = detectIntent($message, $lang);
$response = buildResponse($intent, $message, $conv, $lang);

// -- Fallback AI API -------------------------------------------
if ($response === null && setting('chatbot_ai_actif', '0') === '1') {
    $response = callAI($message, $history, $conv, $lang);
    $intent   = 'ai_generated';
}

// Réponse par défaut si tout échoue
if ($response === null) {
    $response = $lang === 'en'
        ? "I'm not sure I understood your question. Would you like to [contact our team](contact) or learn about our [services](services)?"
        : "Je n'ai pas bien compris votre question. Souhaitez-vous [contacter notre équipe](contact) ou découvrir nos [services](services) ?";
    $intent = 'fallback';
}

// Sauvegarde réponse bot
$db->prepare("INSERT INTO chatbot_messages (conversation_id, role, contenu, intent) VALUES (?,?,?,?)")
   ->execute([$convId, 'bot', $response, $intent]);

json_response(true, '', [
    'reply'       => $response,
    'intent'      => $intent,
    'session_id'  => $sessId,
    'quick_replies' => getQuickReplies($intent, $lang),
]);

// ================================================================
//  DÉTECTION D'INTENTION
// ================================================================
function detectIntent(string $msg, string $lang): string {
    $msg = mb_strtolower(trim($msg), 'UTF-8');

    $intents = [
        'salutation'   => ['bonjour','bonsoir','salut','hello','hi','hey','good morning','good evening'],
        'services'     => ['service','offre','proposez','faites','activité','que faites','spécialité','expertise','que fait igotech','what do you do','offer','provide'],
        'infrastructure'=> ['réseau','serveur','infrastructure','réseau','installation','cisco','linux','wifi','network','server'],
        'developpement' => ['site web','application','développement','développer','coder','programmer','web','app','mobile','logiciel','software','website'],
        'ia_data'      => ['intelligence artificielle','ia','machine learning','data','analyse','chatbot','automatisation','ai','artificial'],
        'formations'   => ['formation','cours','apprendre','étudier','programme','inscription','stage','mentorat','training','learn','course','program'],
        'prix_tarif'   => ['prix','tarif','coût','combien','devis','budget','cost','price','how much','quote'],
        'contact'      => ['contact','email','téléphone','appeler','joindre','whatsapp','message','phone','reach','call'],
        'localisation' => ['où','adresse','localisation','location','N\'djamena','quartier','situer','where','located'],
        'portfolio'    => ['projet','réalisation','client','référence','portfolio','example','work','done'],
        'equipe'       => ['équipe','fondateur','directeur','staff','who','team','qui êtes'],
        'rdv'          => ['rendez-vous','rendez vous','appointment','réunion','meeting','discuter','parler','rdv'],
        'nom'          => ['je m\'appelle','mon nom','name is','i am','je suis','c\'est'],
        'email_capture'=> ['@','mon email','my email','adresse mail'],
        'tel_capture'  => ['+235','00235','06','07','numéro','téléphone'],
        'au_revoir'    => ['merci','au revoir','bye','goodbye','bonne journée','à bientôt','thank you','thanks'],
        'humeur'       => ['comment vas','ça va','how are you','bien','bonne'],
    ];

    foreach ($intents as $intent => $keywords) {
        foreach ($keywords as $kw) {
            if (str_contains($msg, $kw)) return $intent;
        }
    }

    return 'unknown';
}

// ================================================================
//  CONSTRUCTION DES RÉPONSES
// ================================================================
function buildResponse(string $intent, string $msg, array $conv, string $lang): ?string {
    $nom = $conv['visiteur_nom'] ? htmlspecialchars($conv['visiteur_nom']) : '';
    $fr = $lang === 'fr';

    switch ($intent) {

        case 'salutation':
            $greet = $nom ? ($fr ? "Ravi de vous revoir, **{$nom}** !" : "Great to see you again, **{$nom}**!") : '';
            return $fr
                ? "Bonjour ! 👋 {$greet}\nJe suis **Igo**, l'assistant virtuel d'IgoTech. Je peux vous renseigner sur nos services, formations, tarifs ou vous mettre en relation avec notre équipe.\n\nQue puis-je faire pour vous ?"
                : "Hello! 👋 {$greet}\nI'm **Igo**, IgoTech's virtual assistant. I can tell you about our services, training programs, pricing, or connect you with our team.\n\nHow can I help you?";

        case 'humeur':
            return $fr
                ? "Je vais très bien, merci ! 😊 Et vous ?\nEn quoi puis-je vous aider aujourd'hui ?"
                : "I'm doing great, thanks! 😊 And you?\nHow can I help you today?";

        case 'services':
            return $fr
                ? "IgoTech propose **6 domaines de services** :\n\n🖥️ **Infrastructure IT** -- Réseaux, serveurs, sécurité\n💻 **Développement Web & Apps** -- Sites, plateformes, mobiles\n🤖 **Solutions IA & Data** -- Automatisation, analytics, chatbots\n💡 **Conseil Digital** -- Stratégie, audit, transformation\n🛒 **Matériel IT** -- Vente d'équipements informatiques\n🎓 **Formations Tech** -- Dev web, IA, marketing digital\n\nQuel domaine vous intéresse le plus ?"
                : "IgoTech offers **6 service areas**:\n\n🖥️ **IT Infrastructure** -- Networks, servers, security\n💻 **Web & App Development** -- Sites, platforms, mobile\n🤖 **AI & Data Solutions** -- Automation, analytics, chatbots\n💡 **Digital Consulting** -- Strategy, audit, transformation\n🛒 **IT Hardware** -- Equipment sales\n🎓 **Tech Training** -- Web dev, AI, digital marketing\n\nWhich area interests you most?";

        case 'infrastructure':
            return $fr
                ? "Pour l'**infrastructure IT**, IgoTech propose :\n\n• Installation de réseaux LAN/WAN & WiFi\n• Administration de serveurs Windows/Linux\n• Solutions de sauvegarde & reprise d'activité\n• Firewall et sécurité réseau\n• Maintenance et surveillance proactive\n• Solutions cloud & virtualisation\n\nSouhaitez-vous un **devis gratuit** pour votre infrastructure ?"
                : "For **IT infrastructure**, IgoTech offers:\n\n• LAN/WAN & WiFi network installation\n• Windows/Linux server administration\n• Backup & disaster recovery solutions\n• Firewall and network security\n• Proactive maintenance & monitoring\n• Cloud & virtualization solutions\n\nWould you like a **free quote** for your infrastructure?";

        case 'developpement':
            return $fr
                ? "Notre équipe de développement crée :\n\n🌐 **Sites web** -- Institutionnels, e-commerce, portfolios\n⚙️ **Applications web** -- ERP, CRM, portails métier\n📱 **Applications mobiles** -- Android & iOS\n🔗 **API & intégrations** -- Connexion à vos outils existants\n\nNous travaillons principalement avec PHP, MySQL, JavaScript, et des frameworks modernes.\n\nAvez-vous un projet en tête ? Décrivez-le moi ! 💬"
                : "Our dev team builds:\n\n🌐 **Websites** -- Institutional, e-commerce, portfolios\n⚙️ **Web apps** -- ERP, CRM, business portals\n📱 **Mobile apps** -- Android & iOS\n🔗 **API & integrations** -- Connect your existing tools\n\nDo you have a project in mind? Tell me about it! 💬";

        case 'ia_data':
            return $fr
                ? "Nos solutions **IA & Data** incluent :\n\n🤖 **Chatbots intelligents** -- Comme moi ! 😄\n📊 **Tableaux de bord BI** -- Analyse et visualisation de données\n⚡ **Automatisation RPA** -- Tâches répétitives automatisées\n🔍 **Machine Learning** -- Modèles prédictifs sur mesure\n💬 **NLP** -- Traitement automatique du langage\n\nL'IA peut transformer vos processus métier. Vous souhaitez en savoir plus ?"
                : "Our **AI & Data** solutions include:\n\n🤖 **Intelligent chatbots** -- Like me! 😄\n📊 **BI dashboards** -- Data analysis and visualization\n⚡ **RPA Automation** -- Automated repetitive tasks\n🔍 **Machine Learning** -- Custom predictive models\n💬 **NLP** -- Natural language processing\n\nAI can transform your business processes. Want to know more?";

        case 'formations':
            return $fr
                ? "IgoTech propose **5 formations professionnelles** :\n\n1️⃣ **Développement Web Full-Stack** -- 3 mois | 150 000 FCFA\n2️⃣ **Infrastructure IT & Réseaux** -- 2 mois | 120 000 FCFA\n3️⃣ **Marketing Digital** -- 1 mois | 80 000 FCFA\n4️⃣ **IA & Data Analytics** -- 2 mois | 180 000 FCFA\n5️⃣ **Entrepreneuriat Tech** -- 6 semaines | 60 000 FCFA\n\n✅ Certificat officiel | Petits groupes (max 15) | 70% pratique\n\nQuelle formation vous intéresse ? Je peux vous aider à vous inscrire !"
                : "IgoTech offers **5 professional training programs**:\n\n1️⃣ **Full-Stack Web Development** -- 3 months | 150,000 FCFA\n2️⃣ **IT Infrastructure & Networks** -- 2 months | 120,000 FCFA\n3️⃣ **Digital Marketing** -- 1 month | 80,000 FCFA\n4️⃣ **AI & Data Analytics** -- 2 months | 180,000 FCFA\n5️⃣ **Tech Entrepreneurship** -- 6 weeks | 60,000 FCFA\n\n✅ Official certificate | Small groups (max 15) | 70% practical\n\nWhich program interests you? I can help you register!";

        case 'prix_tarif':
            return $fr
                ? "Nos tarifs varient selon les projets et services :\n\n💰 **Formations** -- de 60 000 à 180 000 FCFA\n🌐 **Site web** -- Devis personnalisé (selon complexité)\n⚙️ **Infrastructure IT** -- Sur devis après audit\n🤝 **Conseil** -- Taux journalier ou forfait projet\n\n👉 La meilleure façon d'avoir un prix précis est de nous envoyer votre cahier des charges.\n\nPuis-je prendre votre **email** pour qu'un conseiller vous contacte avec un devis détaillé ?"
                : "Our rates vary by project and service:\n\n💰 **Training** -- 60,000 to 180,000 FCFA\n🌐 **Website** -- Custom quote (based on complexity)\n⚙️ **IT Infrastructure** -- Quote after audit\n🤝 **Consulting** -- Daily rate or project package\n\n👉 The best way to get an accurate price is to send us your requirements.\n\nCan I get your **email** so an advisor can contact you with a detailed quote?";

        case 'contact':
            return $fr
                ? "Vous pouvez nous joindre par :\n\n📞 **Téléphone** -- +235 63 03 87 80\n📱 **WhatsApp** -- +235 92 57 80 57\n📧 **Email** -- contact@igotech.tech\n📍 **Adresse** -- Quartier Amtoukoui, N'Djamena, Tchad\n🕐 **Horaires** -- Lun-Ven : 8h00 - 18h00\n\nOu utilisez notre [formulaire de contact](/contact.php) en ligne.\n\nVous préférez qu'on vous rappelle ? Donnez-moi votre numéro ! 📲"
                : "You can reach us at:\n\n📞 **Phone** -- +235 63 03 87 80\n📱 **WhatsApp** -- +235 92 57 80 57\n📧 **Email** -- contact@igotech.tech\n📍 **Address** -- Quartier Amtoukoui, N'Djamena, Chad\n🕐 **Hours** -- Mon-Fri: 8:00 AM - 6:00 PM\n\nOr use our online [contact form](/contact.php).\n\nWould you like us to call you back? Give me your number! 📲";

        case 'localisation':
            return $fr
                ? "IgoTech est situé à :\n\n📍 **Quartier Amtoukoui**\nRue de Canal Chari-Mongo, Porte 1286\n**N'Djamena, Tchad**\n\nNous sommes facilement accessibles et nous nous déplaçons aussi chez nos clients à N'Djamena et en province.\n\nVoulez-vous prendre rendez-vous pour une visite ? 🤝"
                : "IgoTech is located at:\n\n📍 **Amtoukoui District**\nChari-Mongo Canal Road, Door 1286\n**N'Djamena, Chad**\n\nWe are easily accessible and also visit clients in N'Djamena and the provinces.\n\nWould you like to schedule a visit? 🤝";

        case 'portfolio':
            return $fr
                ? "Parmi nos réalisations :\n\n✅ **Plateforme RH** -- Application web de gestion des ressources humaines\n✅ **Système de caisse** -- POS & gestion des stocks pour PME\n✅ **Site multilingue** -- Portail FR/EN pour ONG internationale\n✅ **Infrastructure réseau** -- Déploiement réseau sécurisé d'entreprise\n\nNous intervenons pour des entreprises locales, ONG et institutions publiques au Tchad.\n\nVoulez-vous voir plus de détails sur un projet ?"
                : "Among our achievements:\n\n✅ **HR Platform** -- Web application for human resource management\n✅ **POS System** -- Cash register & inventory for SMEs\n✅ **Multilingual Site** -- FR/EN portal for international NGO\n✅ **Network infrastructure** -- Secure enterprise network deployment\n\nWe work for local businesses, NGOs and public institutions in Chad.\n\nWould you like more details on a project?";

        case 'equipe':
            return $fr
                ? "IgoTech est une équipe de **professionnels passionnés** par le digital :\n\n👨‍💼 Une direction expérimentée dans les projets IT\n👨‍💻 Des développeurs web & mobile\n🧠 Des experts en IA et data science\n📚 Des formateurs pédagogues\n🎯 Des consultants en stratégie digitale\n\nNotre mission : rendre la technologie accessible aux entreprises et jeunes tchadiens.\n\nEnvie de rejoindre l'équipe ou de collaborer ?"
                : "IgoTech is a team of **passionate digital professionals**:\n\n👨‍💼 Experienced IT project management\n👨‍💻 Web & mobile developers\n🧠 AI and data science experts\n📚 Educational trainers\n🎯 Digital strategy consultants\n\nOur mission: making technology accessible to Chadian businesses and youth.\n\nInterested in joining the team or collaborating?";

        case 'rdv':
            return $fr
                ? "Super ! Je serais ravi d'organiser une rencontre avec notre équipe. 📅\n\nPour planifier votre rendez-vous, j'ai besoin de :\n1. Votre **nom**\n2. Votre **numéro de téléphone** ou **email**\n3. L'**objet de la réunion** (projet, formation, info…)\n\nCommençons : quel est votre **nom** s'il vous plaît ?"
                : "Great! I'd be happy to arrange a meeting with our team. 📅\n\nTo schedule your appointment, I need:\n1. Your **name**\n2. Your **phone number** or **email**\n3. The **purpose of the meeting** (project, training, info…)\n\nLet's start: what is your **name** please?";

        case 'nom':
            $extractedNom = extractName($message);
            if ($extractedNom) {
                return $fr
                    ? "Enchanté, **{$extractedNom}** ! 😊\nPour que notre équipe puisse vous contacter, pouvez-vous me donner votre **adresse email** ou **numéro de téléphone** ?"
                    : "Nice to meet you, **{$extractedNom}**! 😊\nSo our team can reach you, could you give me your **email address** or **phone number**?";
            }
            return null;

        case 'email_capture':
            return $fr
                ? "Merci pour votre email ! ✅\nUn membre de notre équipe vous contactera très bientôt.\n\nY a-t-il autre chose que je puisse faire pour vous en attendant ?"
                : "Thank you for your email! ✅\nA member of our team will contact you very soon.\n\nIs there anything else I can help you with in the meantime?";

        case 'tel_capture':
            return $fr
                ? "Parfait, j'ai bien noté votre numéro ! 📲\nNotre équipe vous rappellera dans les plus brefs délais (généralement dans la journée).\n\nY a-t-il autre chose dont vous avez besoin ?"
                : "Perfect, I've noted your number! 📲\nOur team will call you back as soon as possible (usually the same day).\n\nIs there anything else you need?";

        case 'au_revoir':
            return $fr
                ? "C'était un plaisir de vous aider ! 😊\nN'hésitez pas à revenir si vous avez d'autres questions.\n**À bientôt sur IgoTech !** 🚀"
                : "It was a pleasure helping you! 😊\nDon't hesitate to come back if you have more questions.\n**See you soon on IgoTech!** 🚀";
    }

    return null;
}

// ================================================================
//  QUICK REPLIES PAR INTENTION
// ================================================================
function getQuickReplies(string $intent, string $lang): array {
    $fr = $lang === 'fr';
    $qr = [
        'salutation'    => $fr ? ['Vos services','Vos formations','Tarifs','Nous contacter'] : ['Your services','Training','Pricing','Contact us'],
        'services'      => $fr ? ['Développement web','Infrastructure IT','Solutions IA','Demander un devis'] : ['Web development','IT Infrastructure','AI Solutions','Get a quote'],
        'formations'    => $fr ? ['S\'inscrire','Prix des formations','Prochaine session','En savoir plus'] : ['Register','Training prices','Next session','Learn more'],
        'prix_tarif'    => $fr ? ['Obtenir un devis','Formations','Contact','WhatsApp'] : ['Get a quote','Training','Contact','WhatsApp'],
        'contact'       => $fr ? ['Formulaire contact','WhatsApp direct','Prendre RDV','Nos services'] : ['Contact form','Direct WhatsApp','Book meeting','Our services'],
        'au_revoir'     => $fr ? ['Nos services','Formations','Contact'] : ['Our services','Training','Contact'],
        'fallback'      => $fr ? ['Nos services','Formations','Tarifs','Nous contacter'] : ['Our services','Training','Pricing','Contact us'],
        'default'       => $fr ? ['Services','Formations','Contact','Devis gratuit'] : ['Services','Training','Contact','Free quote'],
    ];
    return $qr[$intent] ?? $qr['default'];
}

// ================================================================
//  CAPTURE INFORMATIONS DE CONTACT
// ================================================================
function detectLeadInfo(string $msg, array $conv): array {
    $updates = [];

    // Email
    if (!$conv['visiteur_email'] && preg_match('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $msg, $m)) {
        if (filter_var($m[0], FILTER_VALIDATE_EMAIL)) {
            $updates['visiteur_email'] = $m[0];
        }
    }

    // Téléphone (formats tchadiens et internationaux)
    if (!$conv['visiteur_tel'] && preg_match('/(\+?235\s?[\d\s]{8,12}|0[679]\d{8}|[\+]?\d{9,13})/', $msg, $m)) {
        $updates['visiteur_tel'] = preg_replace('/\s+/', '', $m[0]);
    }

    // Nom (si pas encore capturé)
    if (!$conv['visiteur_nom']) {
        $nom = extractName($msg);
        if ($nom) $updates['visiteur_nom'] = $nom;
    }

    return $updates;
}

function extractName(string $msg): ?string {
    $patterns = [
        '/je m\'appelle\s+([A-ZÀ-Ü][a-zà-ü]+(?:\s+[A-ZÀ-Ü][a-zà-ü]+)?)/ui',
        '/mon nom est\s+([A-ZÀ-Ü][a-zà-ü]+(?:\s+[A-ZÀ-Ü][a-zà-ü]+)?)/ui',
        '/i am\s+([A-Z][a-z]+(?:\s+[A-Z][a-z]+)?)/ui',
        '/my name is\s+([A-Z][a-z]+(?:\s+[A-Z][a-z]+)?)/ui',
        '/^([A-ZÀ-Ü][a-zà-ü]+(?:\s+[A-ZÀ-Ü][a-zà-ü]+)?)$/u',
    ];
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $msg, $m)) {
            return trim($m[1]);
        }
    }
    return null;
}

// ================================================================
//  SYSTÈME PROMPT PARTAGÉ
// ================================================================
function getSystemPrompt(string $lang): string {
    return $lang === 'fr'
        ? "Tu es **Igo**, l'assistant commercial virtuel d'IgoTech, une agence digitale basée à N'Djamena, au Tchad.\n\nIgoTech propose :\n- Infrastructure IT (réseaux, serveurs, sécurité)\n- Développement web & mobile (sites, applications, e-commerce)\n- Solutions IA & data analytics\n- Conseil en transformation digitale\n- Vente de matériel informatique\n- Imprimerie (flyers, affiches, cartes de visite, packaging)\n- Énergie solaire (études de faisabilité, audit énergétique, caractérisation panneaux PV)\n- Formations tech (développement web, IA, marketing digital, entrepreneuriat)\n\nContacts : +235 63 03 87 80 | contact@igotech.tech | Quartier Amtoukoui, N'Djamena\n\nTu réponds en français, de façon chaleureuse, professionnelle et concise (max 3-4 phrases). Tu utilises des emojis avec modération. Tu orientes toujours vers une action (contact, devis, inscription). Ne jamais inventer de prix précis non mentionnés."
        : "You are **Igo**, the virtual commercial assistant for IgoTech, a digital agency based in N'Djamena, Chad.\n\nIgoTech offers:\n- IT Infrastructure (networks, servers, security)\n- Web & mobile development (sites, apps, e-commerce)\n- AI & data analytics solutions\n- Digital transformation consulting\n- IT hardware sales\n- Printing services (flyers, posters, business cards, packaging)\n- Solar energy (feasibility studies, energy audits, PV panel characterization)\n- Tech training (web dev, AI, digital marketing, entrepreneurship)\n\nContacts: +235 63 03 87 80 | contact@igotech.tech | Amtoukoui District, N'Djamena\n\nReply in English, warmly, professionally and concisely (max 3-4 sentences). Use emojis sparingly. Always guide toward an action (contact, quote, registration). Never invent prices not mentioned.";
}

// ================================================================
//  DISPATCHER IA
// ================================================================
function callAI(string $message, array $history, array $conv, string $lang): ?string {
    $provider = setting('chatbot_ai_provider', 'claude');
    switch ($provider) {
        case 'groq':   return callGroqAPI($message, $history, $lang);
        case 'gemini': return callGeminiAPI($message, $history, $lang);
        default:       return callClaudeAPI($message, $history, $lang);
    }
}

// ================================================================
//  GROQ API (Llama 3, gratuit)
//  Clé gratuite : console.groq.com
// ================================================================
function callGroqAPI(string $message, array $history, string $lang): ?string {
    $apiKey = setting('chatbot_ai_key', '');
    $model  = setting('chatbot_ai_model', 'llama-3.1-8b-instant');

    if (!$apiKey) return null;

    $messages = [['role' => 'system', 'content' => getSystemPrompt($lang)]];
    foreach ($history as $h) {
        $messages[] = ['role' => $h['role'] === 'bot' ? 'assistant' : 'user', 'content' => $h['contenu']];
    }
    $messages[] = ['role' => 'user', 'content' => $message];

    $payload = json_encode([
        'model'       => $model,
        'max_tokens'  => 500,
        'temperature' => 0.7,
        'messages'    => $messages,
    ]);

    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) return null;

    $data = json_decode($response, true);
    return $data['choices'][0]['message']['content'] ?? null;
}

// ================================================================
//  GOOGLE GEMINI API (gratuit)
//  Clé gratuite : aistudio.google.com
// ================================================================
function callGeminiAPI(string $message, array $history, string $lang): ?string {
    $apiKey = setting('chatbot_ai_key', '');
    $model  = setting('chatbot_ai_model', 'gemini-1.5-flash');

    if (!$apiKey) return null;

    $contents = [];
    foreach ($history as $h) {
        $contents[] = [
            'role'  => $h['role'] === 'bot' ? 'model' : 'user',
            'parts' => [['text' => $h['contenu']]],
        ];
    }
    $contents[] = ['role' => 'user', 'parts' => [['text' => $message]]];

    $payload = json_encode([
        'systemInstruction' => ['parts' => [['text' => getSystemPrompt($lang)]]],
        'contents'          => $contents,
        'generationConfig'  => ['maxOutputTokens' => 500, 'temperature' => 0.7],
    ]);

    $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . urlencode($model) . ':generateContent?key=' . urlencode($apiKey);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) return null;

    $data = json_decode($response, true);
    return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
}

// ================================================================
//  CLAUDE API (Anthropic, payant)
// ================================================================
function callClaudeAPI(string $message, array $history, string $lang): ?string {
    $apiKey = setting('chatbot_ai_key', '');
    $model  = setting('chatbot_ai_model', 'claude-haiku-4-5-20251001');

    if (!$apiKey) return null;

    $messages = [];
    foreach ($history as $h) {
        $messages[] = ['role' => $h['role'] === 'bot' ? 'assistant' : 'user', 'content' => $h['contenu']];
    }
    $messages[] = ['role' => 'user', 'content' => $message];

    $payload = json_encode([
        'model'      => $model,
        'max_tokens' => 500,
        'system'     => getSystemPrompt($lang),
        'messages'   => $messages,
    ]);

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01',
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) return null;

    $data = json_decode($response, true);
    return $data['content'][0]['text'] ?? null;
}
