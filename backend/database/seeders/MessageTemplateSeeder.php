<?php

namespace Database\Seeders;

use App\Models\MessageTemplate;
use Illuminate\Database\Seeder;

class MessageTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = $this->getTemplates();

        foreach ($templates as $template) {
            MessageTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                $template
            );
        }

        $this->command->info('Seeded ' . count($templates) . ' message templates');
    }

    /**
     * Get all template definitions
     */
    protected function getTemplates(): array
    {
        return array_merge(
            $this->getEmailTemplates(),
            $this->getSmsTemplates()
        );
    }

    /**
     * Language definitions with sort order
     */
    protected function getLanguages(): array
    {
        return [
            'en' => ['name' => 'English', 'flag' => '🇬🇧', 'sort' => 1],
            'it' => ['name' => 'Italian', 'flag' => '🇮🇹', 'sort' => 2],
            'es' => ['name' => 'Spanish', 'flag' => '🇪🇸', 'sort' => 3],
            'de' => ['name' => 'German', 'flag' => '🇩🇪', 'sort' => 4],
            'fr' => ['name' => 'French', 'flag' => '🇫🇷', 'sort' => 5],
            'ja' => ['name' => 'Japanese', 'flag' => '🇯🇵', 'sort' => 6],
            'el' => ['name' => 'Greek', 'flag' => '🇬🇷', 'sort' => 7],
            'tr' => ['name' => 'Turkish', 'flag' => '🇹🇷', 'sort' => 8],
            'ko' => ['name' => 'Korean', 'flag' => '🇰🇷', 'sort' => 9],
            'pt' => ['name' => 'Portuguese', 'flag' => '🇵🇹', 'sort' => 10],
        ];
    }

    /**
     * Email templates - Main channel for sending tickets
     */
    protected function getEmailTemplates(): array
    {
        $languages = $this->getLanguages();
        $templates = [];

        // ==================== ENGLISH ====================
        $templates[] = [
            'name' => 'Email Ticket (English)',
            'slug' => 'email-ticket-en',
            'channel' => 'email',
            'language' => 'en',
            'language_name' => $languages['en']['name'],
            'language_flag' => $languages['en']['flag'],
            'template_type' => 'ticket_only',
            'subject' => 'Your Uffizi Gallery Tickets – Entry Information',
            'content' => 'Your Uffizi Gallery Tickets – Entry Information

Dear {customer_name},

Thank you for choosing Florence with Locals. Please find your Uffizi Gallery tickets attached.

Entry Instructions
Please proceed directly to Door 01 at the Uffizi Gallery. Present your PDF ticket to the staff and continue through security. There is no need to meet a representative or collect any physical tickets—your attached PDF tickets may be displayed on your mobile device.

Important Information
We recommend arriving at Door 01 at least 15 minutes before your scheduled entry time. While your tickets grant priority entry (bypassing the ticket purchase queue), all visitors must pass through security screening, which may result in a wait.

Booking Details
Date: {tour_date}
Time: {tour_time}
Reference: {reference_number}
Guests: {pax}

Enhance Your Visit
To make the most of your experience, we invite you to explore our online guide. This interactive resource includes a comprehensive history of the Uffizi Gallery, along with detailed information about the masterpieces located on the first and second floors. Access it here: https://uffizi.florencewithlocals.com/

Enjoy your visit!
Florence with Locals',
            'is_default' => true,
            'is_active' => true,
            'sort_order' => $languages['en']['sort'],
        ];

        $templates[] = [
            'name' => 'Email Ticket + Audio (English)',
            'slug' => 'email-ticket-audio-en',
            'channel' => 'email',
            'language' => 'en',
            'language_name' => $languages['en']['name'],
            'language_flag' => $languages['en']['flag'],
            'template_type' => 'ticket_with_audio',
            'subject' => 'Your Uffizi Gallery Tickets – Entry and Audio Guide Information',
            'content' => 'Your Uffizi Gallery Tickets – Entry and Audio Guide Information

Dear {customer_name},

Thank you for choosing Florence with Locals. Please find your Uffizi Gallery tickets attached.

Entry Instructions
Please proceed directly to Door 01 at the Uffizi Gallery. Present your PDF ticket to the staff and continue through security. There is no need to meet a representative or collect any physical tickets—your attached PDF tickets may be displayed on your mobile device.

Important Information
We recommend arriving at Door 01 at least 15 minutes before your scheduled entry time. While your tickets grant priority entry (bypassing the ticket purchase queue), all visitors must pass through security screening, which may result in a wait.

Booking Details
Date: {tour_date}
Time: {tour_time}
Reference: {reference_number}
Guests: {pax}

Enhance Your Visit
To make the most of your experience, we invite you to explore our online guide. This interactive resource includes a comprehensive history of the Uffizi Gallery, along with detailed information about the masterpieces located on the first and second floors. Access it here: https://uffizi.florencewithlocals.com/

Please Note
• A physical guide is not included with this booking.
• Uffizi Gallery tickets are nominative (name-specific). Please bring valid identification matching the name on your ticket, as the museum may refuse entry if the details do not correspond.

Audio Guide Setup Instructions
If you have selected the audio guide option, please follow the steps below to install the app on your mobile device:

1. Scan the QR Code – Scan the QR code provided in the attached PDF document to download and install the POP Guide app on your smartphone.
2. Alternatively, Download from the App Store – You may also search for "POP Guide" in the Apple App Store or Google Play Store and install the application directly.
3. Log In and Select Your Language – Once installed, open the app and enter the username and password provided below. Then select your preferred language from the available options.
4. Use Offline During Your Visit – After completing the setup, the audio guide can be used offline during your visit.

Audio Guide Credentials
Link: {audio_guide_url}
Username: {audio_guide_username}
Password: {audio_guide_password}

Enjoy your visit!
Florence with Locals',
            'is_default' => true,
            'is_active' => true,
            'sort_order' => $languages['en']['sort'],
        ];

        // ==================== ITALIAN ====================
        $templates[] = [
            'name' => 'Email Ticket (Italian)',
            'slug' => 'email-ticket-it',
            'channel' => 'email',
            'language' => 'it',
            'language_name' => $languages['it']['name'],
            'language_flag' => $languages['it']['flag'],
            'template_type' => 'ticket_only',
            'subject' => 'I Tuoi Biglietti per la Galleria degli Uffizi – Informazioni di Ingresso',
            'content' => 'I Tuoi Biglietti per la Galleria degli Uffizi – Informazioni di Ingresso

Gentile {customer_name},

Grazie per aver scelto Florence with Locals. In allegato troverai i tuoi biglietti per la Galleria degli Uffizi.

Istruzioni per l\'Ingresso
Procedi direttamente alla Porta 01 della Galleria degli Uffizi. Mostra il tuo biglietto PDF al personale e prosegui attraverso i controlli di sicurezza. Non è necessario incontrare un rappresentante o ritirare biglietti fisici—i tuoi biglietti PDF allegati possono essere mostrati sul tuo dispositivo mobile.

Informazioni Importanti
Ti consigliamo di arrivare alla Porta 01 almeno 15 minuti prima dell\'orario di ingresso previsto. Sebbene i tuoi biglietti garantiscano l\'ingresso prioritario (saltando la coda per l\'acquisto), tutti i visitatori devono passare attraverso i controlli di sicurezza, che potrebbero comportare un\'attesa.

Dettagli della Prenotazione
Data: {tour_date}
Ora: {tour_time}
Riferimento: {reference_number}
Ospiti: {pax}

Migliora la Tua Visita
Per sfruttare al meglio la tua esperienza, ti invitiamo a esplorare la nostra guida online. Questa risorsa interattiva include una storia completa della Galleria degli Uffizi, insieme a informazioni dettagliate sui capolavori situati al primo e secondo piano. Accedi qui: https://uffizi.florencewithlocals.com/

Buona visita!
Florence with Locals',
            'is_default' => true,
            'is_active' => true,
            'sort_order' => $languages['it']['sort'],
        ];

        $templates[] = [
            'name' => 'Email Ticket + Audio (Italian)',
            'slug' => 'email-ticket-audio-it',
            'channel' => 'email',
            'language' => 'it',
            'language_name' => $languages['it']['name'],
            'language_flag' => $languages['it']['flag'],
            'template_type' => 'ticket_with_audio',
            'subject' => 'I Tuoi Biglietti per la Galleria degli Uffizi – Informazioni di Ingresso e Audioguida',
            'content' => 'I Tuoi Biglietti per la Galleria degli Uffizi – Informazioni di Ingresso e Audioguida

Gentile {customer_name},

Grazie per aver scelto Florence with Locals. In allegato troverai i tuoi biglietti per la Galleria degli Uffizi.

Istruzioni per l\'Ingresso
Procedi direttamente alla Porta 01 della Galleria degli Uffizi. Mostra il tuo biglietto PDF al personale e prosegui attraverso i controlli di sicurezza. Non è necessario incontrare un rappresentante o ritirare biglietti fisici—i tuoi biglietti PDF allegati possono essere mostrati sul tuo dispositivo mobile.

Informazioni Importanti
Ti consigliamo di arrivare alla Porta 01 almeno 15 minuti prima dell\'orario di ingresso previsto. Sebbene i tuoi biglietti garantiscano l\'ingresso prioritario (saltando la coda per l\'acquisto), tutti i visitatori devono passare attraverso i controlli di sicurezza, che potrebbero comportare un\'attesa.

Dettagli della Prenotazione
Data: {tour_date}
Ora: {tour_time}
Riferimento: {reference_number}
Ospiti: {pax}

Migliora la Tua Visita
Per sfruttare al meglio la tua esperienza, ti invitiamo a esplorare la nostra guida online. Questa risorsa interattiva include una storia completa della Galleria degli Uffizi, insieme a informazioni dettagliate sui capolavori situati al primo e secondo piano. Accedi qui: https://uffizi.florencewithlocals.com/

Nota Bene
• Una guida fisica non è inclusa in questa prenotazione.
• I biglietti della Galleria degli Uffizi sono nominativi. Ti preghiamo di portare un documento d\'identità valido che corrisponda al nome sul tuo biglietto, poiché il museo potrebbe rifiutare l\'ingresso se i dati non corrispondono.

Istruzioni per la Configurazione dell\'Audioguida
Se hai selezionato l\'opzione audioguida, segui i passaggi seguenti per installare l\'app sul tuo dispositivo mobile:

1. Scansiona il Codice QR – Scansiona il codice QR fornito nel documento PDF allegato per scaricare e installare l\'app POP Guide sul tuo smartphone.
2. In alternativa, Scarica dall\'App Store – Puoi anche cercare "POP Guide" nell\'Apple App Store o Google Play Store e installare l\'applicazione direttamente.
3. Accedi e Seleziona la Tua Lingua – Una volta installata, apri l\'app e inserisci il nome utente e la password forniti di seguito. Poi seleziona la tua lingua preferita dalle opzioni disponibili.
4. Usa Offline Durante la Tua Visita – Dopo aver completato la configurazione, l\'audioguida può essere utilizzata offline durante la tua visita.

Credenziali Audioguida
Link: {audio_guide_url}
Username: {audio_guide_username}
Password: {audio_guide_password}

Buona visita!
Florence with Locals',
            'is_default' => true,
            'is_active' => true,
            'sort_order' => $languages['it']['sort'],
        ];

        // ==================== SPANISH ====================
        $templates[] = [
            'name' => 'Email Ticket (Spanish)',
            'slug' => 'email-ticket-es',
            'channel' => 'email',
            'language' => 'es',
            'language_name' => $languages['es']['name'],
            'language_flag' => $languages['es']['flag'],
            'template_type' => 'ticket_only',
            'subject' => 'Tus Entradas para la Galería Uffizi – Información de Entrada',
            'content' => 'Tus Entradas para la Galería Uffizi – Información de Entrada

Estimado/a {customer_name},

Gracias por elegir Florence with Locals. Adjunto encontrarás tus entradas para la Galería Uffizi.

Instrucciones de Entrada
Dirígete directamente a la Puerta 01 de la Galería Uffizi. Presenta tu entrada en PDF al personal y continúa a través del control de seguridad. No es necesario reunirse con un representante ni recoger entradas físicas—tus entradas PDF adjuntas pueden mostrarse en tu dispositivo móvil.

Información Importante
Recomendamos llegar a la Puerta 01 al menos 15 minutos antes de tu hora de entrada programada. Aunque tus entradas otorgan entrada prioritaria (evitando la cola de compra), todos los visitantes deben pasar por el control de seguridad, lo que puede resultar en una espera.

Detalles de la Reserva
Fecha: {tour_date}
Hora: {tour_time}
Referencia: {reference_number}
Invitados: {pax}

Mejora Tu Visita
Para aprovechar al máximo tu experiencia, te invitamos a explorar nuestra guía en línea. Este recurso interactivo incluye una historia completa de la Galería Uffizi, junto con información detallada sobre las obras maestras ubicadas en el primer y segundo piso. Accede aquí: https://uffizi.florencewithlocals.com/

¡Disfruta tu visita!
Florence with Locals',
            'is_default' => true,
            'is_active' => true,
            'sort_order' => $languages['es']['sort'],
        ];

        $templates[] = [
            'name' => 'Email Ticket + Audio (Spanish)',
            'slug' => 'email-ticket-audio-es',
            'channel' => 'email',
            'language' => 'es',
            'language_name' => $languages['es']['name'],
            'language_flag' => $languages['es']['flag'],
            'template_type' => 'ticket_with_audio',
            'subject' => 'Tus Entradas para la Galería Uffizi – Información de Entrada y Audioguía',
            'content' => 'Tus Entradas para la Galería Uffizi – Información de Entrada y Audioguía

Estimado/a {customer_name},

Gracias por elegir Florence with Locals. Adjunto encontrarás tus entradas para la Galería Uffizi.

Instrucciones de Entrada
Dirígete directamente a la Puerta 01 de la Galería Uffizi. Presenta tu entrada en PDF al personal y continúa a través del control de seguridad. No es necesario reunirse con un representante ni recoger entradas físicas—tus entradas PDF adjuntas pueden mostrarse en tu dispositivo móvil.

Información Importante
Recomendamos llegar a la Puerta 01 al menos 15 minutos antes de tu hora de entrada programada. Aunque tus entradas otorgan entrada prioritaria (evitando la cola de compra), todos los visitantes deben pasar por el control de seguridad, lo que puede resultar en una espera.

Detalles de la Reserva
Fecha: {tour_date}
Hora: {tour_time}
Referencia: {reference_number}
Invitados: {pax}

Mejora Tu Visita
Para aprovechar al máximo tu experiencia, te invitamos a explorar nuestra guía en línea. Este recurso interactivo incluye una historia completa de la Galería Uffizi, junto con información detallada sobre las obras maestras ubicadas en el primer y segundo piso. Accede aquí: https://uffizi.florencewithlocals.com/

Nota Importante
• No se incluye un guía físico con esta reserva.
• Las entradas de la Galería Uffizi son nominativas. Por favor, lleva una identificación válida que coincida con el nombre en tu entrada, ya que el museo puede rechazar la entrada si los datos no corresponden.

Instrucciones de Configuración de la Audioguía
Si has seleccionado la opción de audioguía, sigue los pasos a continuación para instalar la aplicación en tu dispositivo móvil:

1. Escanea el Código QR – Escanea el código QR proporcionado en el documento PDF adjunto para descargar e instalar la aplicación POP Guide en tu smartphone.
2. Alternativamente, Descarga desde la App Store – También puedes buscar "POP Guide" en Apple App Store o Google Play Store e instalar la aplicación directamente.
3. Inicia Sesión y Selecciona Tu Idioma – Una vez instalada, abre la aplicación e introduce el nombre de usuario y la contraseña proporcionados a continuación. Luego selecciona tu idioma preferido de las opciones disponibles.
4. Usa Sin Conexión Durante Tu Visita – Después de completar la configuración, la audioguía puede usarse sin conexión durante tu visita.

Credenciales de la Audioguía
Enlace: {audio_guide_url}
Usuario: {audio_guide_username}
Contraseña: {audio_guide_password}

¡Disfruta tu visita!
Florence with Locals',
            'is_default' => true,
            'is_active' => true,
            'sort_order' => $languages['es']['sort'],
        ];

        // ==================== GERMAN ====================
        $templates[] = [
            'name' => 'Email Ticket (German)',
            'slug' => 'email-ticket-de',
            'channel' => 'email',
            'language' => 'de',
            'language_name' => $languages['de']['name'],
            'language_flag' => $languages['de']['flag'],
            'template_type' => 'ticket_only',
            'subject' => 'Ihre Uffizien-Tickets – Eintrittsinformationen',
            'content' => 'Ihre Uffizien-Tickets – Eintrittsinformationen

Lieber {customer_name},

Vielen Dank, dass Sie sich für Florence with Locals entschieden haben. Anbei finden Sie Ihre Tickets für die Uffizien-Galerie.

Eintrittsinstruktionen
Gehen Sie direkt zur Tür 01 der Uffizien-Galerie. Zeigen Sie Ihr PDF-Ticket dem Personal und gehen Sie durch die Sicherheitskontrolle. Es ist nicht notwendig, einen Vertreter zu treffen oder physische Tickets abzuholen—Ihre angehängten PDF-Tickets können auf Ihrem Mobilgerät angezeigt werden.

Wichtige Informationen
Wir empfehlen, mindestens 15 Minuten vor Ihrer geplanten Eintrittszeit an Tür 01 anzukommen. Obwohl Ihre Tickets prioritären Eintritt gewähren (unter Umgehung der Ticketkaufschlange), müssen alle Besucher die Sicherheitskontrolle passieren, was zu einer Wartezeit führen kann.

Buchungsdetails
Datum: {tour_date}
Uhrzeit: {tour_time}
Referenz: {reference_number}
Gäste: {pax}

Verbessern Sie Ihren Besuch
Um das Beste aus Ihrem Erlebnis zu machen, laden wir Sie ein, unseren Online-Führer zu erkunden. Diese interaktive Ressource enthält eine umfassende Geschichte der Uffizien-Galerie sowie detaillierte Informationen über die Meisterwerke im ersten und zweiten Stock. Zugang hier: https://uffizi.florencewithlocals.com/

Genießen Sie Ihren Besuch!
Florence with Locals',
            'is_default' => true,
            'is_active' => true,
            'sort_order' => $languages['de']['sort'],
        ];

        $templates[] = [
            'name' => 'Email Ticket + Audio (German)',
            'slug' => 'email-ticket-audio-de',
            'channel' => 'email',
            'language' => 'de',
            'language_name' => $languages['de']['name'],
            'language_flag' => $languages['de']['flag'],
            'template_type' => 'ticket_with_audio',
            'subject' => 'Ihre Uffizien-Tickets – Eintritts- und Audioguide-Informationen',
            'content' => 'Ihre Uffizien-Tickets – Eintritts- und Audioguide-Informationen

Lieber {customer_name},

Vielen Dank, dass Sie sich für Florence with Locals entschieden haben. Anbei finden Sie Ihre Tickets für die Uffizien-Galerie.

Eintrittsinstruktionen
Gehen Sie direkt zur Tür 01 der Uffizien-Galerie. Zeigen Sie Ihr PDF-Ticket dem Personal und gehen Sie durch die Sicherheitskontrolle. Es ist nicht notwendig, einen Vertreter zu treffen oder physische Tickets abzuholen—Ihre angehängten PDF-Tickets können auf Ihrem Mobilgerät angezeigt werden.

Wichtige Informationen
Wir empfehlen, mindestens 15 Minuten vor Ihrer geplanten Eintrittszeit an Tür 01 anzukommen. Obwohl Ihre Tickets prioritären Eintritt gewähren (unter Umgehung der Ticketkaufschlange), müssen alle Besucher die Sicherheitskontrolle passieren, was zu einer Wartezeit führen kann.

Buchungsdetails
Datum: {tour_date}
Uhrzeit: {tour_time}
Referenz: {reference_number}
Gäste: {pax}

Verbessern Sie Ihren Besuch
Um das Beste aus Ihrem Erlebnis zu machen, laden wir Sie ein, unseren Online-Führer zu erkunden. Diese interaktive Ressource enthält eine umfassende Geschichte der Uffizien-Galerie sowie detaillierte Informationen über die Meisterwerke im ersten und zweiten Stock. Zugang hier: https://uffizi.florencewithlocals.com/

Bitte Beachten
• Ein physischer Führer ist in dieser Buchung nicht enthalten.
• Uffizien-Galerie-Tickets sind namentlich. Bitte bringen Sie einen gültigen Ausweis mit, der dem Namen auf Ihrem Ticket entspricht, da das Museum den Eintritt verweigern kann, wenn die Daten nicht übereinstimmen.

Audioguide-Einrichtungsanleitung
Wenn Sie die Audioguide-Option gewählt haben, befolgen Sie bitte die folgenden Schritte, um die App auf Ihrem Mobilgerät zu installieren:

1. QR-Code Scannen – Scannen Sie den QR-Code im angehängten PDF-Dokument, um die POP Guide App herunterzuladen und auf Ihrem Smartphone zu installieren.
2. Alternativ aus dem App Store Herunterladen – Sie können auch "POP Guide" im Apple App Store oder Google Play Store suchen und die Anwendung direkt installieren.
3. Anmelden und Sprache Wählen – Nach der Installation öffnen Sie die App und geben Sie den unten angegebenen Benutzernamen und das Passwort ein. Wählen Sie dann Ihre bevorzugte Sprache aus den verfügbaren Optionen.
4. Offline Während Ihres Besuchs Nutzen – Nach Abschluss der Einrichtung kann der Audioguide während Ihres Besuchs offline verwendet werden.

Audioguide-Zugangsdaten
Link: {audio_guide_url}
Benutzername: {audio_guide_username}
Passwort: {audio_guide_password}

Genießen Sie Ihren Besuch!
Florence with Locals',
            'is_default' => true,
            'is_active' => true,
            'sort_order' => $languages['de']['sort'],
        ];

        // ==================== FRENCH ====================
        $templates[] = [
            'name' => 'Email Ticket (French)',
            'slug' => 'email-ticket-fr',
            'channel' => 'email',
            'language' => 'fr',
            'language_name' => $languages['fr']['name'],
            'language_flag' => $languages['fr']['flag'],
            'template_type' => 'ticket_only',
            'subject' => 'Vos Billets pour la Galerie des Offices – Informations d\'Entrée',
            'content' => 'Vos Billets pour la Galerie des Offices – Informations d\'Entrée

Cher/Chère {customer_name},

Merci d\'avoir choisi Florence with Locals. Veuillez trouver vos billets pour la Galerie des Offices en pièce jointe.

Instructions d\'Entrée
Rendez-vous directement à la Porte 01 de la Galerie des Offices. Présentez votre billet PDF au personnel et passez le contrôle de sécurité. Il n\'est pas nécessaire de rencontrer un représentant ou de récupérer des billets physiques—vos billets PDF joints peuvent être affichés sur votre appareil mobile.

Informations Importantes
Nous recommandons d\'arriver à la Porte 01 au moins 15 minutes avant votre heure d\'entrée prévue. Bien que vos billets accordent une entrée prioritaire (en évitant la file d\'achat), tous les visiteurs doivent passer le contrôle de sécurité, ce qui peut entraîner une attente.

Détails de la Réservation
Date: {tour_date}
Heure: {tour_time}
Référence: {reference_number}
Invités: {pax}

Améliorez Votre Visite
Pour profiter au maximum de votre expérience, nous vous invitons à explorer notre guide en ligne. Cette ressource interactive comprend une histoire complète de la Galerie des Offices, ainsi que des informations détaillées sur les chefs-d\'œuvre situés aux premier et deuxième étages. Accédez ici: https://uffizi.florencewithlocals.com/

Bonne visite!
Florence with Locals',
            'is_default' => true,
            'is_active' => true,
            'sort_order' => $languages['fr']['sort'],
        ];

        $templates[] = [
            'name' => 'Email Ticket + Audio (French)',
            'slug' => 'email-ticket-audio-fr',
            'channel' => 'email',
            'language' => 'fr',
            'language_name' => $languages['fr']['name'],
            'language_flag' => $languages['fr']['flag'],
            'template_type' => 'ticket_with_audio',
            'subject' => 'Vos Billets pour la Galerie des Offices – Informations d\'Entrée et Audioguide',
            'content' => 'Vos Billets pour la Galerie des Offices – Informations d\'Entrée et Audioguide

Cher/Chère {customer_name},

Merci d\'avoir choisi Florence with Locals. Veuillez trouver vos billets pour la Galerie des Offices en pièce jointe.

Instructions d\'Entrée
Rendez-vous directement à la Porte 01 de la Galerie des Offices. Présentez votre billet PDF au personnel et passez le contrôle de sécurité. Il n\'est pas nécessaire de rencontrer un représentant ou de récupérer des billets physiques—vos billets PDF joints peuvent être affichés sur votre appareil mobile.

Informations Importantes
Nous recommandons d\'arriver à la Porte 01 au moins 15 minutes avant votre heure d\'entrée prévue. Bien que vos billets accordent une entrée prioritaire (en évitant la file d\'achat), tous les visiteurs doivent passer le contrôle de sécurité, ce qui peut entraîner une attente.

Détails de la Réservation
Date: {tour_date}
Heure: {tour_time}
Référence: {reference_number}
Invités: {pax}

Améliorez Votre Visite
Pour profiter au maximum de votre expérience, nous vous invitons à explorer notre guide en ligne. Cette ressource interactive comprend une histoire complète de la Galerie des Offices, ainsi que des informations détaillées sur les chefs-d\'œuvre situés aux premier et deuxième étages. Accédez ici: https://uffizi.florencewithlocals.com/

Remarque
• Un guide physique n\'est pas inclus dans cette réservation.
• Les billets de la Galerie des Offices sont nominatifs. Veuillez apporter une pièce d\'identité valide correspondant au nom sur votre billet, car le musée peut refuser l\'entrée si les informations ne correspondent pas.

Instructions de Configuration de l\'Audioguide
Si vous avez sélectionné l\'option audioguide, veuillez suivre les étapes ci-dessous pour installer l\'application sur votre appareil mobile:

1. Scannez le Code QR – Scannez le code QR fourni dans le document PDF joint pour télécharger et installer l\'application POP Guide sur votre smartphone.
2. Alternativement, Téléchargez depuis l\'App Store – Vous pouvez également rechercher "POP Guide" dans l\'Apple App Store ou Google Play Store et installer l\'application directement.
3. Connectez-vous et Sélectionnez Votre Langue – Une fois installée, ouvrez l\'application et entrez le nom d\'utilisateur et le mot de passe fournis ci-dessous. Puis sélectionnez votre langue préférée parmi les options disponibles.
4. Utilisez Hors Ligne Pendant Votre Visite – Après avoir terminé la configuration, l\'audioguide peut être utilisé hors ligne pendant votre visite.

Identifiants de l\'Audioguide
Lien: {audio_guide_url}
Identifiant: {audio_guide_username}
Mot de passe: {audio_guide_password}

Bonne visite!
Florence with Locals',
            'is_default' => true,
            'is_active' => true,
            'sort_order' => $languages['fr']['sort'],
        ];

        // ==================== JAPANESE ====================
        $templates[] = [
            'name' => 'Email Ticket (Japanese)',
            'slug' => 'email-ticket-ja',
            'channel' => 'email',
            'language' => 'ja',
            'language_name' => $languages['ja']['name'],
            'language_flag' => $languages['ja']['flag'],
            'template_type' => 'ticket_only',
            'subject' => 'ウフィツィ美術館チケット – 入場案内',
            'content' => 'ウフィツィ美術館チケット – 入場案内

{customer_name} 様

Florence with Localsをお選びいただきありがとうございます。ウフィツィ美術館のチケットを添付いたします。

入場方法
ウフィツィ美術館の第1番ドアへ直接お進みください。PDFチケットをスタッフに提示し、セキュリティチェックを通過してください。代表者と会う必要や物理的なチケットを受け取る必要はありません。添付のPDFチケットはモバイルデバイスで表示できます。

重要な情報
予定入場時刻の少なくとも15分前に第1番ドアに到着することをお勧めします。チケットは優先入場を許可しますが（チケット購入列をスキップ）、すべての訪問者はセキュリティスクリーニングを通過する必要があり、待ち時間が発生する場合があります。

予約詳細
日付: {tour_date}
時間: {tour_time}
参照番号: {reference_number}
ゲスト: {pax}

訪問をより充実させる
体験を最大限に活かすため、オンラインガイドをご覧ください。このインタラクティブなリソースには、ウフィツィ美術館の包括的な歴史と、1階と2階にある傑作についての詳細情報が含まれています。こちらからアクセス: https://uffizi.florencewithlocals.com/

素晴らしい訪問をお楽しみください！
Florence with Locals',
            'is_default' => true,
            'is_active' => true,
            'sort_order' => $languages['ja']['sort'],
        ];

        $templates[] = [
            'name' => 'Email Ticket + Audio (Japanese)',
            'slug' => 'email-ticket-audio-ja',
            'channel' => 'email',
            'language' => 'ja',
            'language_name' => $languages['ja']['name'],
            'language_flag' => $languages['ja']['flag'],
            'template_type' => 'ticket_with_audio',
            'subject' => 'ウフィツィ美術館チケット – 入場およびオーディオガイド案内',
            'content' => 'ウフィツィ美術館チケット – 入場およびオーディオガイド案内

{customer_name} 様

Florence with Localsをお選びいただきありがとうございます。ウフィツィ美術館のチケットを添付いたします。

入場方法
ウフィツィ美術館の第1番ドアへ直接お進みください。PDFチケットをスタッフに提示し、セキュリティチェックを通過してください。代表者と会う必要や物理的なチケットを受け取る必要はありません。添付のPDFチケットはモバイルデバイスで表示できます。

重要な情報
予定入場時刻の少なくとも15分前に第1番ドアに到着することをお勧めします。チケットは優先入場を許可しますが（チケット購入列をスキップ）、すべての訪問者はセキュリティスクリーニングを通過する必要があり、待ち時間が発生する場合があります。

予約詳細
日付: {tour_date}
時間: {tour_time}
参照番号: {reference_number}
ゲスト: {pax}

訪問をより充実させる
体験を最大限に活かすため、オンラインガイドをご覧ください。このインタラクティブなリソースには、ウフィツィ美術館の包括的な歴史と、1階と2階にある傑作についての詳細情報が含まれています。こちらからアクセス: https://uffizi.florencewithlocals.com/

ご注意
• この予約には物理的なガイドは含まれていません。
• ウフィツィ美術館のチケットは記名式です。チケットに記載された名前と一致する有効な身分証明書をお持ちください。詳細が一致しない場合、美術館は入場を拒否する場合があります。

オーディオガイド設定手順
オーディオガイドオプションを選択された場合は、以下の手順に従ってモバイルデバイスにアプリをインストールしてください：

1. QRコードをスキャン – 添付のPDF文書に記載されているQRコードをスキャンして、スマートフォンにPOP Guideアプリをダウンロードしてインストールします。
2. または、App Storeからダウンロード – Apple App StoreまたはGoogle Play Storeで「POP Guide」を検索し、アプリケーションを直接インストールすることもできます。
3. ログインして言語を選択 – インストール後、アプリを開き、以下に記載されているユーザー名とパスワードを入力します。次に、利用可能なオプションからお好みの言語を選択してください。
4. 訪問中はオフラインで使用 – 設定完了後、オーディオガイドは訪問中オフラインで使用できます。

オーディオガイド認証情報
リンク: {audio_guide_url}
ユーザー名: {audio_guide_username}
パスワード: {audio_guide_password}

素晴らしい訪問をお楽しみください！
Florence with Locals',
            'is_default' => true,
            'is_active' => true,
            'sort_order' => $languages['ja']['sort'],
        ];

        // ==================== GREEK ====================
        $templates[] = [
            'name' => 'Email Ticket (Greek)',
            'slug' => 'email-ticket-el',
            'channel' => 'email',
            'language' => 'el',
            'language_name' => $languages['el']['name'],
            'language_flag' => $languages['el']['flag'],
            'template_type' => 'ticket_only',
            'subject' => 'Τα Εισιτήριά σας για την Πινακοθήκη Ουφίτσι – Πληροφορίες Εισόδου',
            'content' => 'Τα Εισιτήριά σας για την Πινακοθήκη Ουφίτσι – Πληροφορίες Εισόδου

Αγαπητέ/ή {customer_name},

Ευχαριστούμε που επιλέξατε το Florence with Locals. Επισυνάπτονται τα εισιτήριά σας για την Πινακοθήκη Ουφίτσι.

Οδηγίες Εισόδου
Κατευθυνθείτε απευθείας στην Πόρτα 01 της Πινακοθήκης Ουφίτσι. Δείξτε το PDF εισιτήριό σας στο προσωπικό και περάστε τον έλεγχο ασφαλείας. Δεν χρειάζεται να συναντήσετε εκπρόσωπο ή να παραλάβετε φυσικά εισιτήρια—τα επισυναπτόμενα PDF εισιτήριά σας μπορούν να εμφανιστούν στην κινητή σας συσκευή.

Σημαντικές Πληροφορίες
Συνιστούμε να φτάσετε στην Πόρτα 01 τουλάχιστον 15 λεπτά πριν την προγραμματισμένη ώρα εισόδου σας. Ενώ τα εισιτήριά σας παρέχουν προτεραιότητα εισόδου (παρακάμπτοντας την ουρά αγοράς), όλοι οι επισκέπτες πρέπει να περάσουν έλεγχο ασφαλείας, ο οποίος μπορεί να προκαλέσει αναμονή.

Λεπτομέρειες Κράτησης
Ημερομηνία: {tour_date}
Ώρα: {tour_time}
Αναφορά: {reference_number}
Επισκέπτες: {pax}

Βελτιώστε την Επίσκεψή σας
Για να αξιοποιήσετε στο έπακρο την εμπειρία σας, σας προσκαλούμε να εξερευνήσετε τον online οδηγό μας. Αυτός ο διαδραστικός πόρος περιλαμβάνει μια ολοκληρωμένη ιστορία της Πινακοθήκης Ουφίτσι, μαζί με λεπτομερείς πληροφορίες για τα αριστουργήματα που βρίσκονται στον πρώτο και δεύτερο όροφο. Πρόσβαση εδώ: https://uffizi.florencewithlocals.com/

Καλή επίσκεψη!
Florence with Locals',
            'is_default' => true,
            'is_active' => true,
            'sort_order' => $languages['el']['sort'],
        ];

        $templates[] = [
            'name' => 'Email Ticket + Audio (Greek)',
            'slug' => 'email-ticket-audio-el',
            'channel' => 'email',
            'language' => 'el',
            'language_name' => $languages['el']['name'],
            'language_flag' => $languages['el']['flag'],
            'template_type' => 'ticket_with_audio',
            'subject' => 'Τα Εισιτήριά σας για την Πινακοθήκη Ουφίτσι – Πληροφορίες Εισόδου και Ηχητικού Οδηγού',
            'content' => 'Τα Εισιτήριά σας για την Πινακοθήκη Ουφίτσι – Πληροφορίες Εισόδου και Ηχητικού Οδηγού

Αγαπητέ/ή {customer_name},

Ευχαριστούμε που επιλέξατε το Florence with Locals. Επισυνάπτονται τα εισιτήριά σας για την Πινακοθήκη Ουφίτσι.

Οδηγίες Εισόδου
Κατευθυνθείτε απευθείας στην Πόρτα 01 της Πινακοθήκης Ουφίτσι. Δείξτε το PDF εισιτήριό σας στο προσωπικό και περάστε τον έλεγχο ασφαλείας. Δεν χρειάζεται να συναντήσετε εκπρόσωπο ή να παραλάβετε φυσικά εισιτήρια—τα επισυναπτόμενα PDF εισιτήριά σας μπορούν να εμφανιστούν στην κινητή σας συσκευή.

Σημαντικές Πληροφορίες
Συνιστούμε να φτάσετε στην Πόρτα 01 τουλάχιστον 15 λεπτά πριν την προγραμματισμένη ώρα εισόδου σας. Ενώ τα εισιτήριά σας παρέχουν προτεραιότητα εισόδου (παρακάμπτοντας την ουρά αγοράς), όλοι οι επισκέπτες πρέπει να περάσουν έλεγχο ασφαλείας, ο οποίος μπορεί να προκαλέσει αναμονή.

Λεπτομέρειες Κράτησης
Ημερομηνία: {tour_date}
Ώρα: {tour_time}
Αναφορά: {reference_number}
Επισκέπτες: {pax}

Βελτιώστε την Επίσκεψή σας
Για να αξιοποιήσετε στο έπακρο την εμπειρία σας, σας προσκαλούμε να εξερευνήσετε τον online οδηγό μας. Αυτός ο διαδραστικός πόρος περιλαμβάνει μια ολοκληρωμένη ιστορία της Πινακοθήκης Ουφίτσι, μαζί με λεπτομερείς πληροφορίες για τα αριστουργήματα που βρίσκονται στον πρώτο και δεύτερο όροφο. Πρόσβαση εδώ: https://uffizi.florencewithlocals.com/

Σημείωση
• Δεν περιλαμβάνεται φυσικός ξεναγός σε αυτήν την κράτηση.
• Τα εισιτήρια της Πινακοθήκης Ουφίτσι είναι ονομαστικά. Παρακαλώ φέρτε έγκυρη ταυτότητα που να ταιριάζει με το όνομα στο εισιτήριό σας, καθώς το μουσείο μπορεί να αρνηθεί την είσοδο εάν τα στοιχεία δεν αντιστοιχούν.

Οδηγίες Ρύθμισης Ηχητικού Οδηγού
Εάν έχετε επιλέξει την επιλογή ηχητικού οδηγού, ακολουθήστε τα παρακάτω βήματα για να εγκαταστήσετε την εφαρμογή στην κινητή σας συσκευή:

1. Σαρώστε τον Κωδικό QR – Σαρώστε τον κωδικό QR που παρέχεται στο επισυναπτόμενο έγγραφο PDF για να κατεβάσετε και εγκαταστήσετε την εφαρμογή POP Guide στο smartphone σας.
2. Εναλλακτικά, Κατεβάστε από το App Store – Μπορείτε επίσης να αναζητήσετε "POP Guide" στο Apple App Store ή Google Play Store και να εγκαταστήσετε την εφαρμογή απευθείας.
3. Συνδεθείτε και Επιλέξτε τη Γλώσσα σας – Μόλις εγκατασταθεί, ανοίξτε την εφαρμογή και εισάγετε το όνομα χρήστη και τον κωδικό πρόσβασης που παρέχονται παρακάτω. Στη συνέχεια επιλέξτε τη γλώσσα προτίμησής σας από τις διαθέσιμες επιλογές.
4. Χρησιμοποιήστε Εκτός Σύνδεσης Κατά την Επίσκεψή σας – Μετά την ολοκλήρωση της ρύθμισης, ο ηχητικός οδηγός μπορεί να χρησιμοποιηθεί εκτός σύνδεσης κατά την επίσκεψή σας.

Διαπιστευτήρια Ηχητικού Οδηγού
Σύνδεσμος: {audio_guide_url}
Όνομα χρήστη: {audio_guide_username}
Κωδικός πρόσβασης: {audio_guide_password}

Καλή επίσκεψη!
Florence with Locals',
            'is_default' => true,
            'is_active' => true,
            'sort_order' => $languages['el']['sort'],
        ];

        // ==================== TURKISH ====================
        $templates[] = [
            'name' => 'Email Ticket (Turkish)',
            'slug' => 'email-ticket-tr',
            'channel' => 'email',
            'language' => 'tr',
            'language_name' => $languages['tr']['name'],
            'language_flag' => $languages['tr']['flag'],
            'template_type' => 'ticket_only',
            'subject' => 'Uffizi Galerisi Biletleriniz – Giriş Bilgileri',
            'content' => 'Uffizi Galerisi Biletleriniz – Giriş Bilgileri

Sayın {customer_name},

Florence with Locals\'ı tercih ettiğiniz için teşekkür ederiz. Uffizi Galerisi biletlerinizi ekte bulabilirsiniz.

Giriş Talimatları
Doğrudan Uffizi Galerisi\'nin 01 numaralı kapısına gidin. PDF biletinizi personele gösterin ve güvenlik kontrolünden geçin. Bir temsilciyle buluşmanıza veya fiziksel bilet almanıza gerek yoktur—ekli PDF biletleriniz mobil cihazınızda gösterilebilir.

Önemli Bilgiler
Planlanan giriş saatinizden en az 15 dakika önce 01 numaralı kapıya varmanızı öneririz. Biletleriniz öncelikli giriş sağlasa da (bilet satın alma kuyruğunu atlayarak), tüm ziyaretçilerin güvenlik taramasından geçmesi gerekmektedir, bu da beklemeye neden olabilir.

Rezervasyon Detayları
Tarih: {tour_date}
Saat: {tour_time}
Referans: {reference_number}
Misafirler: {pax}

Ziyaretinizi Geliştirin
Deneyiminizden en iyi şekilde yararlanmak için çevrimiçi rehberimizi keşfetmenizi davet ediyoruz. Bu etkileşimli kaynak, Uffizi Galerisi\'nin kapsamlı tarihini ve birinci ile ikinci katlardaki şaheserlere ilişkin ayrıntılı bilgileri içermektedir. Buradan erişin: https://uffizi.florencewithlocals.com/

İyi ziyaretler!
Florence with Locals',
            'is_default' => true,
            'is_active' => true,
            'sort_order' => $languages['tr']['sort'],
        ];

        $templates[] = [
            'name' => 'Email Ticket + Audio (Turkish)',
            'slug' => 'email-ticket-audio-tr',
            'channel' => 'email',
            'language' => 'tr',
            'language_name' => $languages['tr']['name'],
            'language_flag' => $languages['tr']['flag'],
            'template_type' => 'ticket_with_audio',
            'subject' => 'Uffizi Galerisi Biletleriniz – Giriş ve Sesli Rehber Bilgileri',
            'content' => 'Uffizi Galerisi Biletleriniz – Giriş ve Sesli Rehber Bilgileri

Sayın {customer_name},

Florence with Locals\'ı tercih ettiğiniz için teşekkür ederiz. Uffizi Galerisi biletlerinizi ekte bulabilirsiniz.

Giriş Talimatları
Doğrudan Uffizi Galerisi\'nin 01 numaralı kapısına gidin. PDF biletinizi personele gösterin ve güvenlik kontrolünden geçin. Bir temsilciyle buluşmanıza veya fiziksel bilet almanıza gerek yoktur—ekli PDF biletleriniz mobil cihazınızda gösterilebilir.

Önemli Bilgiler
Planlanan giriş saatinizden en az 15 dakika önce 01 numaralı kapıya varmanızı öneririz. Biletleriniz öncelikli giriş sağlasa da (bilet satın alma kuyruğunu atlayarak), tüm ziyaretçilerin güvenlik taramasından geçmesi gerekmektedir, bu da beklemeye neden olabilir.

Rezervasyon Detayları
Tarih: {tour_date}
Saat: {tour_time}
Referans: {reference_number}
Misafirler: {pax}

Ziyaretinizi Geliştirin
Deneyiminizden en iyi şekilde yararlanmak için çevrimiçi rehberimizi keşfetmenizi davet ediyoruz. Bu etkileşimli kaynak, Uffizi Galerisi\'nin kapsamlı tarihini ve birinci ile ikinci katlardaki şaheserlere ilişkin ayrıntılı bilgileri içermektedir. Buradan erişin: https://uffizi.florencewithlocals.com/

Not
• Bu rezervasyona fiziksel bir rehber dahil değildir.
• Uffizi Galerisi biletleri isim içerir. Biletinizdeki isimle eşleşen geçerli bir kimlik getirin, çünkü müze bilgiler uyuşmuyorsa girişi reddedebilir.

Sesli Rehber Kurulum Talimatları
Sesli rehber seçeneğini seçtiyseniz, uygulamayı mobil cihazınıza yüklemek için aşağıdaki adımları izleyin:

1. QR Kodunu Tarayın – Ekli PDF belgesinde sağlanan QR kodunu tarayarak POP Guide uygulamasını akıllı telefonunuza indirin ve yükleyin.
2. Alternatif olarak, App Store\'dan İndirin – Apple App Store veya Google Play Store\'da "POP Guide" araması yapabilir ve uygulamayı doğrudan yükleyebilirsiniz.
3. Giriş Yapın ve Dilinizi Seçin – Yüklendikten sonra, uygulamayı açın ve aşağıda sağlanan kullanıcı adı ve şifreyi girin. Ardından mevcut seçeneklerden tercih ettiğiniz dili seçin.
4. Ziyaretiniz Sırasında Çevrimdışı Kullanın – Kurulum tamamlandıktan sonra, sesli rehber ziyaretiniz sırasında çevrimdışı kullanılabilir.

Sesli Rehber Kimlik Bilgileri
Bağlantı: {audio_guide_url}
Kullanıcı adı: {audio_guide_username}
Şifre: {audio_guide_password}

İyi ziyaretler!
Florence with Locals',
            'is_default' => true,
            'is_active' => true,
            'sort_order' => $languages['tr']['sort'],
        ];

        // ==================== KOREAN ====================
        $templates[] = [
            'name' => 'Email Ticket (Korean)',
            'slug' => 'email-ticket-ko',
            'channel' => 'email',
            'language' => 'ko',
            'language_name' => $languages['ko']['name'],
            'language_flag' => $languages['ko']['flag'],
            'template_type' => 'ticket_only',
            'subject' => '우피치 미술관 티켓 – 입장 안내',
            'content' => '우피치 미술관 티켓 – 입장 안내

{customer_name} 고객님께,

Florence with Locals를 선택해 주셔서 감사합니다. 우피치 미술관 티켓이 첨부되어 있습니다.

입장 안내
우피치 미술관 1번 문으로 직접 가세요. PDF 티켓을 직원에게 보여주고 보안 검색을 통과하세요. 대리인을 만나거나 실물 티켓을 받을 필요가 없습니다—첨부된 PDF 티켓은 모바일 기기에서 표시할 수 있습니다.

중요 정보
예정된 입장 시간 최소 15분 전에 1번 문에 도착하시기를 권장합니다. 티켓이 우선 입장을 허용하지만(티켓 구매 대기열 우회), 모든 방문객은 보안 검색을 통과해야 하며 이로 인해 대기가 발생할 수 있습니다.

예약 세부 정보
날짜: {tour_date}
시간: {tour_time}
참조 번호: {reference_number}
게스트: {pax}

방문을 더욱 풍요롭게
경험을 최대한 활용하기 위해 온라인 가이드를 탐색해 보시기 바랍니다. 이 인터랙티브 리소스에는 우피치 미술관의 포괄적인 역사와 1층 및 2층에 위치한 걸작에 대한 자세한 정보가 포함되어 있습니다. 여기에서 접속하세요: https://uffizi.florencewithlocals.com/

즐거운 방문 되세요!
Florence with Locals',
            'is_default' => true,
            'is_active' => true,
            'sort_order' => $languages['ko']['sort'],
        ];

        $templates[] = [
            'name' => 'Email Ticket + Audio (Korean)',
            'slug' => 'email-ticket-audio-ko',
            'channel' => 'email',
            'language' => 'ko',
            'language_name' => $languages['ko']['name'],
            'language_flag' => $languages['ko']['flag'],
            'template_type' => 'ticket_with_audio',
            'subject' => '우피치 미술관 티켓 – 입장 및 오디오 가이드 안내',
            'content' => '우피치 미술관 티켓 – 입장 및 오디오 가이드 안내

{customer_name} 고객님께,

Florence with Locals를 선택해 주셔서 감사합니다. 우피치 미술관 티켓이 첨부되어 있습니다.

입장 안내
우피치 미술관 1번 문으로 직접 가세요. PDF 티켓을 직원에게 보여주고 보안 검색을 통과하세요. 대리인을 만나거나 실물 티켓을 받을 필요가 없습니다—첨부된 PDF 티켓은 모바일 기기에서 표시할 수 있습니다.

중요 정보
예정된 입장 시간 최소 15분 전에 1번 문에 도착하시기를 권장합니다. 티켓이 우선 입장을 허용하지만(티켓 구매 대기열 우회), 모든 방문객은 보안 검색을 통과해야 하며 이로 인해 대기가 발생할 수 있습니다.

예약 세부 정보
날짜: {tour_date}
시간: {tour_time}
참조 번호: {reference_number}
게스트: {pax}

방문을 더욱 풍요롭게
경험을 최대한 활용하기 위해 온라인 가이드를 탐색해 보시기 바랍니다. 이 인터랙티브 리소스에는 우피치 미술관의 포괄적인 역사와 1층 및 2층에 위치한 걸작에 대한 자세한 정보가 포함되어 있습니다. 여기에서 접속하세요: https://uffizi.florencewithlocals.com/

참고
• 이 예약에는 실물 가이드가 포함되어 있지 않습니다.
• 우피치 미술관 티켓은 실명제입니다. 티켓에 있는 이름과 일치하는 유효한 신분증을 지참해 주세요. 정보가 일치하지 않으면 미술관에서 입장을 거부할 수 있습니다.

오디오 가이드 설정 안내
오디오 가이드 옵션을 선택하신 경우, 아래 단계에 따라 모바일 기기에 앱을 설치하세요:

1. QR 코드 스캔 – 첨부된 PDF 문서에 제공된 QR 코드를 스캔하여 스마트폰에 POP Guide 앱을 다운로드하고 설치하세요.
2. 또는 앱 스토어에서 다운로드 – Apple App Store 또는 Google Play Store에서 "POP Guide"를 검색하여 앱을 직접 설치할 수도 있습니다.
3. 로그인 및 언어 선택 – 설치 후 앱을 열고 아래 제공된 사용자 이름과 비밀번호를 입력하세요. 그런 다음 사용 가능한 옵션에서 선호하는 언어를 선택하세요.
4. 방문 중 오프라인 사용 – 설정을 완료한 후 오디오 가이드는 방문 중 오프라인으로 사용할 수 있습니다.

오디오 가이드 자격 증명
링크: {audio_guide_url}
사용자 이름: {audio_guide_username}
비밀번호: {audio_guide_password}

즐거운 방문 되세요!
Florence with Locals',
            'is_default' => true,
            'is_active' => true,
            'sort_order' => $languages['ko']['sort'],
        ];

        // ==================== PORTUGUESE ====================
        $templates[] = [
            'name' => 'Email Ticket (Portuguese)',
            'slug' => 'email-ticket-pt',
            'channel' => 'email',
            'language' => 'pt',
            'language_name' => $languages['pt']['name'],
            'language_flag' => $languages['pt']['flag'],
            'template_type' => 'ticket_only',
            'subject' => 'Os Seus Bilhetes para a Galeria Uffizi – Informações de Entrada',
            'content' => 'Os Seus Bilhetes para a Galeria Uffizi – Informações de Entrada

Caro/a {customer_name},

Obrigado por escolher Florence with Locals. Em anexo encontrará os seus bilhetes para a Galeria Uffizi.

Instruções de Entrada
Dirija-se diretamente à Porta 01 da Galeria Uffizi. Apresente o seu bilhete em PDF ao pessoal e passe pelo controlo de segurança. Não é necessário encontrar um representante ou recolher bilhetes físicos—os seus bilhetes PDF anexados podem ser exibidos no seu dispositivo móvel.

Informações Importantes
Recomendamos chegar à Porta 01 pelo menos 15 minutos antes da sua hora de entrada agendada. Embora os seus bilhetes concedam entrada prioritária (evitando a fila de compra), todos os visitantes devem passar pelo controlo de segurança, o que pode resultar em espera.

Detalhes da Reserva
Data: {tour_date}
Hora: {tour_time}
Referência: {reference_number}
Convidados: {pax}

Melhore a Sua Visita
Para aproveitar ao máximo a sua experiência, convidamo-lo a explorar o nosso guia online. Este recurso interativo inclui uma história abrangente da Galeria Uffizi, juntamente com informações detalhadas sobre as obras-primas localizadas no primeiro e segundo andares. Aceda aqui: https://uffizi.florencewithlocals.com/

Boa visita!
Florence with Locals',
            'is_default' => true,
            'is_active' => true,
            'sort_order' => $languages['pt']['sort'],
        ];

        $templates[] = [
            'name' => 'Email Ticket + Audio (Portuguese)',
            'slug' => 'email-ticket-audio-pt',
            'channel' => 'email',
            'language' => 'pt',
            'language_name' => $languages['pt']['name'],
            'language_flag' => $languages['pt']['flag'],
            'template_type' => 'ticket_with_audio',
            'subject' => 'Os Seus Bilhetes para a Galeria Uffizi – Informações de Entrada e Audioguia',
            'content' => 'Os Seus Bilhetes para a Galeria Uffizi – Informações de Entrada e Audioguia

Caro/a {customer_name},

Obrigado por escolher Florence with Locals. Em anexo encontrará os seus bilhetes para a Galeria Uffizi.

Instruções de Entrada
Dirija-se diretamente à Porta 01 da Galeria Uffizi. Apresente o seu bilhete em PDF ao pessoal e passe pelo controlo de segurança. Não é necessário encontrar um representante ou recolher bilhetes físicos—os seus bilhetes PDF anexados podem ser exibidos no seu dispositivo móvel.

Informações Importantes
Recomendamos chegar à Porta 01 pelo menos 15 minutos antes da sua hora de entrada agendada. Embora os seus bilhetes concedam entrada prioritária (evitando a fila de compra), todos os visitantes devem passar pelo controlo de segurança, o que pode resultar em espera.

Detalhes da Reserva
Data: {tour_date}
Hora: {tour_time}
Referência: {reference_number}
Convidados: {pax}

Melhore a Sua Visita
Para aproveitar ao máximo a sua experiência, convidamo-lo a explorar o nosso guia online. Este recurso interativo inclui uma história abrangente da Galeria Uffizi, juntamente com informações detalhadas sobre as obras-primas localizadas no primeiro e segundo andares. Aceda aqui: https://uffizi.florencewithlocals.com/

Nota
• Um guia físico não está incluído nesta reserva.
• Os bilhetes da Galeria Uffizi são nominativos. Por favor, traga uma identificação válida que corresponda ao nome no seu bilhete, pois o museu pode recusar a entrada se os dados não corresponderem.

Instruções de Configuração do Audioguia
Se selecionou a opção de audioguia, siga os passos abaixo para instalar a aplicação no seu dispositivo móvel:

1. Digitalize o Código QR – Digitalize o código QR fornecido no documento PDF anexado para descarregar e instalar a aplicação POP Guide no seu smartphone.
2. Alternativamente, Descarregue da App Store – Também pode procurar "POP Guide" na Apple App Store ou Google Play Store e instalar a aplicação diretamente.
3. Inicie Sessão e Selecione o Seu Idioma – Após a instalação, abra a aplicação e introduza o nome de utilizador e a palavra-passe fornecidos abaixo. Em seguida, selecione o seu idioma preferido das opções disponíveis.
4. Use Offline Durante a Sua Visita – Após concluir a configuração, o audioguia pode ser usado offline durante a sua visita.

Credenciais do Audioguia
Ligação: {audio_guide_url}
Nome de utilizador: {audio_guide_username}
Palavra-passe: {audio_guide_password}

Boa visita!
Florence with Locals',
            'is_default' => true,
            'is_active' => true,
            'sort_order' => $languages['pt']['sort'],
        ];

        return $templates;
    }

    /**
     * SMS templates (short notifications)
     */
    protected function getSmsTemplates(): array
    {
        $languages = $this->getLanguages();

        return [
            [
                'name' => 'SMS Notification (English)',
                'slug' => 'sms-ticket-en',
                'channel' => 'sms',
                'language' => 'en',
                'language_name' => $languages['en']['name'],
                'language_flag' => $languages['en']['flag'],
                'template_type' => 'ticket_only',
                'subject' => null,
                'content' => 'Florence with Locals: Your Uffizi tickets for {tour_date} sent to {customer_email}. Ref: {reference_number}. Door 01, arrive 15min early.',
                'is_default' => true,
                'is_active' => true,
                'sort_order' => $languages['en']['sort'],
            ],
            [
                'name' => 'SMS Notification (Italian)',
                'slug' => 'sms-ticket-it',
                'channel' => 'sms',
                'language' => 'it',
                'language_name' => $languages['it']['name'],
                'language_flag' => $languages['it']['flag'],
                'template_type' => 'ticket_only',
                'subject' => null,
                'content' => 'Florence with Locals: Biglietti Uffizi per {tour_date} inviati a {customer_email}. Rif: {reference_number}. Porta 01, arriva 15min prima.',
                'is_default' => true,
                'is_active' => true,
                'sort_order' => $languages['it']['sort'],
            ],
            [
                'name' => 'SMS Notification (Spanish)',
                'slug' => 'sms-ticket-es',
                'channel' => 'sms',
                'language' => 'es',
                'language_name' => $languages['es']['name'],
                'language_flag' => $languages['es']['flag'],
                'template_type' => 'ticket_only',
                'subject' => null,
                'content' => 'Florence with Locals: Entradas Uffizi para {tour_date} enviadas a {customer_email}. Ref: {reference_number}. Puerta 01, llega 15min antes.',
                'is_default' => true,
                'is_active' => true,
                'sort_order' => $languages['es']['sort'],
            ],
            [
                'name' => 'SMS Notification (German)',
                'slug' => 'sms-ticket-de',
                'channel' => 'sms',
                'language' => 'de',
                'language_name' => $languages['de']['name'],
                'language_flag' => $languages['de']['flag'],
                'template_type' => 'ticket_only',
                'subject' => null,
                'content' => 'Florence with Locals: Uffizi-Tickets für {tour_date} an {customer_email} gesendet. Ref: {reference_number}. Tür 01, 15min früher kommen.',
                'is_default' => true,
                'is_active' => true,
                'sort_order' => $languages['de']['sort'],
            ],
            [
                'name' => 'SMS Notification (French)',
                'slug' => 'sms-ticket-fr',
                'channel' => 'sms',
                'language' => 'fr',
                'language_name' => $languages['fr']['name'],
                'language_flag' => $languages['fr']['flag'],
                'template_type' => 'ticket_only',
                'subject' => null,
                'content' => 'Florence with Locals: Billets Uffizi pour {tour_date} envoyés à {customer_email}. Réf: {reference_number}. Porte 01, arrivez 15min avant.',
                'is_default' => true,
                'is_active' => true,
                'sort_order' => $languages['fr']['sort'],
            ],
        ];
    }
}
