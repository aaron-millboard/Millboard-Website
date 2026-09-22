<?php

namespace Theme\Summit;

/**
 * French wording for the Summit form and gate.
 *
 * The fr-fr page is the only one that is not in English, and its strings are
 * held here rather than in Loco for a practical reason: a Loco translation is
 * a separate manual job, and until somebody does it the French page silently
 * serves English to French partners. That already happened. Keeping the
 * wording in code means the page is right the moment it deploys.
 *
 * English stays on `__()` exactly as before, so nothing here can change what
 * the UK, INT or US pages render. `t()` returns the French string ONLY for the
 * FR audience and falls straight through to the English default otherwise.
 *
 * The consent block was supplied by Aaron on 14 Sep 2026 and is reproduced
 * verbatim; it is the legal wording for legitimate-interest processing, so do
 * not paraphrase it.
 */
class Strings
{
    /**
     * @var array<string,string>
     */
    private const FR = [
        // --- field labels
        'first_name' => 'Prénom',
        'last_name' => 'Nom',
        'email' => 'E-mail professionnel',
        'company' => 'Nom de l\'entreprise',
        'email_hint' => 'Merci d\'utiliser l\'adresse à laquelle votre invitation a été envoyée.',
        'submit' => 'Soumettre',

        // --- page copy
        'intro' => 'Vous avez reçu une invitation personnelle au Millboard Summit. Merci de ne pas '
            . 'transmettre ce lien d\'inscription à d\'autres personnes : les places sont strictement '
            . 'limitées et nous ne pouvons accueillir que deux participants par entreprise. Merci de '
            . 'votre compréhension.',
        /* translators: %s is a list of dates, e.g. "3 et 4 novembre". */
        'assigned_days' => 'Votre invitation couvre les %s.',
        'noscript' => 'Ce formulaire d\'inscription nécessite JavaScript. Merci de l\'activer et de '
            . 'recharger la page, ou de répondre à votre invitation et nous vous inscrirons.',

        // --- shown by the script itself, so they cannot come back from the gate
        'js_busy' => 'Merci de patienter…',
        'js_unavailable' => 'L\'inscription n\'est pas disponible pour le moment. Merci de répondre '
            . 'à votre invitation et nous vous inscrirons.',
        'js_failed' => 'Une erreur est survenue lors de l\'envoi de votre inscription. Merci de '
            . 'réessayer, ou de répondre à votre invitation.',

        // --- consent. Supplied by Aaron, verbatim, do not reword.
        'consent' => 'Les informations que vous fournissez dans ce formulaire sont traitées par '
            . 'Millboard, en tant que responsable du traitement, afin d\'organiser le Millboard Summit '
            . 'et de gérer votre participation.' . "\n\n"
            . 'Nous utiliserons également votre adresse e-mail professionnelle pour vous envoyer des '
            . 'informations sur nos produits, nos événements et notre actualité en rapport avec votre '
            . 'fonction. Nous le faisons sur la base de notre intérêt légitime à communiquer avec les '
            . 'professionnels de notre secteur.' . "\n\n"
            . 'Vous pouvez vous opposer à ces communications à tout moment et sans frais. Cochez la '
            . 'case ci-dessous, utilisez le lien de désabonnement figurant dans tout e-mail que nous '
            . 'vous envoyons, ou envoyez un e-mail à dataprotection@millboard.com.' . "\n\n"
            . 'Notre Politique de confidentialité explique comment nous utilisons vos données et '
            . 'présente vos droits d\'accès, de rectification, de suppression, de limitation, de '
            . 'portabilité et d\'opposition.',
        'opt_out' => 'Je ne souhaite pas recevoir de communications commerciales de la part de Millboard France',
        // The phrase inside `consent` that becomes the privacy-policy link.
        'privacy_phrase' => 'Politique de confidentialité',

        // --- outcomes
        'success_heading' => 'Merci, votre inscription est bien enregistrée.',
        'success_text' => 'Nous reviendrons vers vous avec les détails prochainement.',
        'declined' => 'Merci de nous avoir prévenus.',

        // --- validation
        'err_check_fields' => 'Merci de vérifier les champs indiqués.',
        'err_first_name' => 'Merci d\'indiquer votre prénom.',
        'err_last_name' => 'Merci d\'indiquer votre nom.',
        'err_email' => 'Merci d\'indiquer une adresse e-mail valide.',
        'err_company' => 'Merci d\'indiquer le nom de votre entreprise.',
        'err_attending' => 'Merci de nous indiquer si vous pourrez être présent(e).',
        'select_prompt' => 'Merci de sélectionner',

        // --- refusals
        'not_invited' => 'Nous ne trouvons pas cette adresse e-mail parmi nos invitations. Le Summit '
            . 'est sur invitation uniquement : merci d\'utiliser l\'adresse à laquelle votre invitation '
            . 'a été envoyée. Si vous pensez qu\'il s\'agit d\'une erreur, répondez à votre invitation '
            . 'et nous ferons le nécessaire.',
        'wrong_page' => 'Votre invitation concerne une autre session. Merci d\'utiliser le lien '
            . 'd\'inscription figurant dans votre propre invitation, ou répondez-y et nous vous aiderons.',
        /* translators: 1: cap, 2: company, 3: how many already registered. */
        'company_full' => 'Nous ne pouvons accueillir que %1$d participants par entreprise, et %2$s en '
            . 'compte déjà %3$d. Si vous souhaitez modifier les participants, répondez à votre '
            . 'invitation et nous vous aiderons.',
        /* translators: %s is a date. */
        'day_full_multi' => 'Le %s est complet, et votre invitation couvre plusieurs jours : nous ne '
            . 'pouvons donc pas finaliser votre inscription. Merci de répondre à votre invitation et '
            . 'nous verrons ce qu\'il est possible de faire.',
        'rate_limited' => 'Trop de tentatives depuis votre réseau. Merci de patienter quelques minutes, '
            . 'ou répondez à votre invitation et nous vous inscrirons.',
        'busy' => 'Une autre inscription est en cours. Merci de réessayer dans un instant.',
        'save_failed' => 'Une erreur est survenue lors de l\'enregistrement de votre inscription. '
            . 'Merci de réessayer, ou de répondre à votre invitation.',
        'misconfigured' => 'Ce formulaire d\'inscription n\'est pas correctement configuré. Merci de '
            . 'répondre à votre invitation et nous vous inscrirons.',
    ];

    /**
     * Day numbers for display.
     *
     * The day labels in Audiences are also the keys the registrations are
     * stored under, so they are English by definition and cannot be changed
     * here. These are for showing a French guest their dates, nothing else.
     *
     * @var array<string,string>
     */
    private const FR_DAY_NUMBERS = [
        Audiences::DAY_3RD => '3',
        Audiences::DAY_4TH => '4',
        Audiences::DAY_5TH => '5',
    ];

    private const FR_MONTH = 'novembre';

    /**
     * The assigned days as French reads them: "3 et 4 novembre".
     *
     * The month is said once, which is how the rest of the French page words
     * it. If a day ever turns up that is not one of the three, the labels are
     * returned as they are rather than quietly mislabelling a date.
     *
     * @param array<int,string> $days
     */
    public static function fr_day_list(array $days): string
    {
        $numbers = [];

        foreach ($days as $day) {
            if (!isset(self::FR_DAY_NUMBERS[$day])) {
                return implode(' et ', $days);
            }

            $numbers[] = self::FR_DAY_NUMBERS[$day];
        }

        if ($numbers === []) {
            return '';
        }

        $last = array_pop($numbers);
        $list = $numbers === [] ? $last : implode(', ', $numbers) . ' et ' . $last;

        return $list . ' ' . self::FR_MONTH;
    }

    /**
     * The French string for this key when the audience is FR, otherwise the
     * English default unchanged.
     *
     * @param string $key      Key in self::FR.
     * @param string $audience The audience of the page.
     * @param string $english  The already-translated English default.
     */
    public static function t(string $key, string $audience, string $english): string
    {
        if ($audience !== Audiences::FR) {
            return $english;
        }

        return self::FR[$key] ?? $english;
    }

    /** The raw French string, or '' when there isn't one. */
    public static function fr(string $key): string
    {
        return self::FR[$key] ?? '';
    }
}
