<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Service d'envoi de mails pour FantasyRealm Online
 */
class MailService
{
    /**
     * Les adresses viennent de la configuration (MAILER_FROM et CONTACT_EMAIL),
     * voir config/services.yaml.
     */
    public function __construct(
        private MailerInterface $mailer,
        private string $fromEmail,
        private string $contactEmail,
    ) {
    }

    /**
     * 1. Envoi du formulaire de contact
     */
    public function sendContactForm(string $fromEmail, string $pseudo, string $message): void
    {
        $email = (new Email())
            ->from($this->fromEmail)
            ->to($this->contactEmail)
            ->replyTo($fromEmail)
            ->subject('FantasyRealm - Message de contact de ' . $pseudo)
            ->html(
                '<h2>Nouveau message de contact</h2>' .
                '<p><strong>De :</strong> ' . htmlspecialchars($pseudo) . ' (' . htmlspecialchars($fromEmail) . ')</p>' .
                '<p><strong>Message :</strong></p>' .
                '<p>' . nl2br(htmlspecialchars($message)) . '</p>'
            );

        $this->mailer->send($email);
    }

    /**
     * 2. Envoi du mot de passe temporaire (mot de passe oublié)
     */
    public function sendTemporaryPassword(string $toEmail, string $pseudo, string $temporaryPassword): void
    {
        $email = (new Email())
            ->from($this->fromEmail)
            ->to($toEmail)
            ->subject('FantasyRealm - Votre nouveau mot de passe temporaire')
            ->html(
                '<h2>Réinitialisation de mot de passe</h2>' .
                '<p>Bonjour ' . htmlspecialchars($pseudo) . ',</p>' .
                '<p>Votre mot de passe a été réinitialisé. Voici votre mot de passe temporaire :</p>' .
                '<p style="font-size: 18px; font-weight: bold; background: #333; padding: 10px; border-radius: 5px;">' . htmlspecialchars($temporaryPassword) . '</p>' .
                '<p><strong>Important :</strong> Vous devrez changer ce mot de passe lors de votre prochaine connexion.</p>' .
                '<p>Si vous n\'êtes pas à l\'origine de cette demande, contactez-nous immédiatement.</p>' .
                '<p>L\'équipe PixelVerse Studios</p>'
            );

        $this->mailer->send($email);
    }

    /**
     * 3. Notification : personnage approuvé
     */
    public function sendPersonnageApproved(string $toEmail, string $pseudo, string $nomPersonnage): void
    {
        $email = (new Email())
            ->from($this->fromEmail)
            ->to($toEmail)
            ->subject('FantasyRealm - Votre personnage a été approuvé !')
            ->html(
                '<h2>Personnage approuvé</h2>' .
                '<p>Bonjour ' . htmlspecialchars($pseudo) . ',</p>' .
                '<p>Votre personnage <strong>"' . htmlspecialchars($nomPersonnage) . '"</strong> a été approuvé par notre équipe de modération.</p>' .
                '<p>Il est maintenant visible dans la galerie publique !</p>' .
                '<p>L\'équipe PixelVerse Studios</p>'
            );

        $this->mailer->send($email);
    }

    /**
     * 4. Notification : personnage rejeté (avec motif)
     */
    public function sendPersonnageRejected(string $toEmail, string $pseudo, string $nomPersonnage, string $motif): void
    {
        $email = (new Email())
            ->from($this->fromEmail)
            ->to($toEmail)
            ->subject('FantasyRealm - Votre personnage n\'a pas été approuvé')
            ->html(
                '<h2>Personnage non approuvé</h2>' .
                '<p>Bonjour ' . htmlspecialchars($pseudo) . ',</p>' .
                '<p>Nous sommes désolés, votre personnage <strong>"' . htmlspecialchars($nomPersonnage) . '"</strong> n\'a pas été approuvé par notre équipe de modération.</p>' .
                '<p><strong>Motif :</strong> ' . htmlspecialchars($motif) . '</p>' .
                '<p>Le personnage a été supprimé. Vous pouvez en créer un nouveau en respectant les règles de la communauté.</p>' .
                '<p>L\'équipe PixelVerse Studios</p>'
            );

        $this->mailer->send($email);
    }

    /**
     * 5. Notification : commentaire approuvé sur votre personnage
     */
    public function sendCommentaireApproved(string $toEmail, string $pseudo, string $nomPersonnage): void
    {
        $email = (new Email())
            ->from($this->fromEmail)
            ->to($toEmail)
            ->subject('FantasyRealm - Nouvel avis sur votre personnage')
            ->html(
                '<h2>Nouvel avis visible</h2>' .
                '<p>Bonjour ' . htmlspecialchars($pseudo) . ',</p>' .
                '<p>Un nouvel avis sur votre personnage <strong>"' . htmlspecialchars($nomPersonnage) . '"</strong> a été approuvé et est maintenant visible.</p>' .
                '<p>Connectez-vous pour le consulter !</p>' .
                '<p>L\'équipe PixelVerse Studios</p>'
            );

        $this->mailer->send($email);
    }
}
