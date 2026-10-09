<?php

namespace Theme\Summit;

/**
 * French copy for the Summit pre-arrival form.
 *
 * The French is taken word for word from the approved design. Keys are the
 * English source strings exactly as they appear in the block template, so a
 * French page swaps them in through the gettext filter while the block
 * renders, and the UK and INT output never passes through this file.
 *
 * Only labels and sentences are translated. The values a delegate's answers are
 * stored as stay English (for example "Vegan", "No requirements", "Yes"), so
 * HubSpot reporting reads the same across every audience.
 */
final class ReviewStrings
{
    public const FR = [
        'Before you arrive' => "Avant votre arrivée",
        'Six short steps, all on this page. Please complete them by no later than' => "Six étapes rapides, toutes sur cette page. Merci de les compléter au plus tard le",
        'so we can have everything ready for you.' => "afin que nous puissions tout préparer pour vous.",
        'View the high level agenda (PDF)' => "Consulter le programme (PDF)",
        'Your progress' => "Votre progression",
        'of 6 complete' => "sur 6 complétées",
        'Your details' => "Vos coordonnées",
        'Required' => "Obligatoire",
        'First name' => "Prénom",
        'Last name' => "Nom",
        'Company' => "Société",
        'Email' => "E-mail",
        'Mobile number' => "Numéro de mobile",
        'Including country code, e.g. +33' => "Avec l'indicatif pays, ex. +33",
        'Your travel' => "Votre voyage",
        'Complete' => "Complété",
        'Your arrival details help us plan around your arrival and contact you if anything changes. Transport during the event is arranged by us.' => "Vos informations d'arrivée nous aident à organiser votre accueil et à vous contacter en cas de changement. Les transports pendant l'événement sont organisés par nos soins.",
        "I haven't booked my flight yet. I'll send them to %s once booked." => "Je n'ai pas encore réservé mon vol. J'enverrai les informations à %s dès que possible.",
        'Arriving in the UK' => "Arrivée au Royaume-Uni",
        'Airline' => "Compagnie aérienne",
        'Flight number' => "Numéro de vol",
        'Arrival airport' => "Aéroport d'arrivée",
        'Select' => "Sélectionner",
        'Arrival date' => "Date d'arrivée",
        'Arrival time (UK time)' => "Heure d'arrivée (heure du Royaume-Uni)",
        'Departing from' => "Ville de départ",
        'e.g. Air France' => "ex. Air France",
        'e.g. AF1680' => "ex. AF1680",
        'e.g. Paris CDG' => "ex. Paris CDG",
        'London Heathrow (LHR)' => "Londres Heathrow (LHR)",
        'London Gatwick (LGW)' => "Londres Gatwick (LGW)",
        'Other' => "Autre",
        'Non-Disclosure Agreement' => "Accord de non-divulgation",
        "During the Summit we'll share products and plans ahead of launch. Please read and sign our NDA before attending." => "Pendant le Summit, nous présenterons des produits et des projets avant leur lancement. Merci de lire et d'accepter notre accord de non-divulgation avant l'événement.",
        'Read the NDA (PDF)' => "Lire l'accord de non-divulgation (PDF)",
        'I have read and agree to the Millboard Summit Non-Disclosure Agreement (NDA) on behalf of myself and my business.' => "J'ai lu et j'accepte l'accord de non-divulgation du Millboard Summit, en mon nom et au nom de mon entreprise.",
        'Health and Safety Information' => "Informations santé et sécurité",
        "For the manufacturing site visit, please wear closed-toe flat shoes and follow your host's guidance at all times. Any PPE needed will be provided on the day." => "Pour la visite du site de production, merci de porter des chaussures plates et fermées et de suivre à tout moment les consignes de votre accompagnateur. Les EPI nécessaires vous seront fournis sur place.",
        'Read the Health and Safety Information (PDF)' => "Lire les informations santé et sécurité (PDF)",
        'I have read and understood the Health and Safety Information.' => "J'ai lu et compris les informations santé et sécurité.",
        'Dietary and accessibility requirements' => "Besoins alimentaires et d'accessibilité",
        'Dietary requirements and allergies' => "Régimes alimentaires et allergies",
        'Do you have any dietary requirements or allergies?' => "Avez-vous un régime alimentaire particulier ou des allergies ?",
        'No requirements' => "Aucun besoin particulier",
        "Yes, I'd like to let you know about something" => "Oui, je souhaite vous en informer",
        'Please select all that apply.' => "Merci de sélectionner toutes les options pertinentes.",
        'Vegetarian' => "Végétarien",
        'Vegan' => "Végan",
        'Gluten free' => "Sans gluten",
        'Dairy free' => "Sans lactose",
        'Nut allergy' => "Allergie aux fruits à coque",
        'Anything else we should know?' => "Autre chose à nous signaler ?",
        'For example, severity of an allergy' => "Par exemple, la gravité d'une allergie",
        'Accessibility and mobility' => "Accessibilité et mobilité",
        'We want everyone to be comfortable and fully involved. The site visit includes some walking and time on your feet.' => "Nous souhaitons que chacun se sente à l'aise et pleinement impliqué. La visite du site implique de la marche et du temps debout.",
        'Do you have any access, mobility or other requirements we can help with during the event?' => "Avez-vous des besoins d'accès, de mobilité ou autres pour lesquels nous pouvons vous aider pendant l'événement ?",
        'Please tell us what would help' => "Dites-nous ce qui vous aiderait",
        'For example, step-free access, seating during the tour or a quiet space' => "Par exemple, un accès sans marches, un siège pendant la visite ou un espace calme",
        'This information is kept confidential and shared only with the team organising your visit.' => "Ces informations restent confidentielles et ne sont partagées qu'avec l'équipe qui organise votre visite.",
        'I consent to Millboard using the dietary and accessibility information I give to make arrangements for me at the Summit.' => "J'accepte que Millboard utilise les informations alimentaires et d'accessibilité que je communique afin d'organiser ma participation au Summit.",
        'Photography and filming' => "Photos et vidéos",
        "We'll be capturing moments from the Summit for internal and marketing use." => "Nous immortaliserons des moments du Summit à des fins internes et marketing.",
        'Please choose one option.' => "Merci de choisir une option.",
        "I'm happy to be photographed or filmed and for images of me to be used for the purpose noted above." => "J'accepte d'être photographié(e) ou filmé(e) et que les images me représentant soient utilisées aux fins indiquées ci-dessus.",
        "Please don't photograph or film me." => "Je ne souhaite pas être photographié(e) ni filmé(e).",
        'Competition Law guidelines' => "Règles de droit de la concurrence",
        'The Summit brings together people from across our network, so we ask everyone to agree to our Competition Law guidelines before attending.' => "Le Summit réunit des participants de l'ensemble de notre réseau. Nous demandons donc à chacun d'accepter nos règles de droit de la concurrence avant l'événement.",
        'Read the Competition Law guidelines (PDF)' => "Lire les règles de droit de la concurrence (PDF)",
        'I have read and agree to the Competition Law guidelines.' => "J'ai lu et j'accepte les règles de droit de la concurrence.",
        'Your data' => "Vos données",
        "We'll use the details on this page only to organise your attendance at the Summit." => "Nous utiliserons les informations de cette page uniquement pour organiser votre participation au Summit.",
        'Read our privacy policy (PDF)' => "Lire notre avis de confidentialité (PDF)",
        'I consent to Millboard processing my personal data for the purpose of my attendance at the event.' => "J'accepte que Millboard traite mes données personnelles aux fins de ma participation à l'événement.",
        'Submit' => "Envoyer",
        'Thank you for confirming your attendance. We look forward to seeing you at the Millboard Summit!' => "Merci d'avoir confirmé votre participation. Nous avons hâte de vous accueillir au Millboard Summit.",
        'Questions?' => "Des questions ?",
        'or' => "ou",
        'This form needs JavaScript. Please enable it, or email enquiries@millboard.com.' => "Ce formulaire nécessite JavaScript. Merci de l'activer, ou d'écrire à enquiries@millboard.com.",
        // Shown by the script itself; they reach it through the form's data-i18n.
        'Sending…' => "Envoi…",
        'Thank you, ' => "Merci, ",
        'Ready to submit.' => "Prêt à envoyer.",
        'Still to complete: ' => "Reste à compléter : ",
        'your details' => "vos coordonnées",
        'your travel' => "votre voyage",
        'Sorry, we could not send that. Please try again, or email enquiries@millboard.com.' => "Désolé, nous n'avons pas pu envoyer votre réponse. Merci de réessayer, ou d'écrire à enquiries@millboard.com.",
    ];

    /**
     * Gettext filter that swaps in the French for strings in the theme domain.
     * Added only while an FR block renders, then removed, so nothing else on
     * the page is touched.
     */
    public static function gettext_filter(): \Closure
    {
        return static function ($translated, $text, $domain) {
            return $domain === 'granola' && isset(self::FR[$text]) ? self::FR[$text] : $translated;
        };
    }
}
