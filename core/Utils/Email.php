<?php

namespace Zap\Core\Utils;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Class Email
 * 
 * Handles SMTP email configuration, composition, file attachment processing,
 * and dispatching via PHPMailer.
 */
class Email
{
    /** @var array Sender email address */
    protected array $sender = [];

    /** @var string Sender email password */
    protected string $password;

    /** @var array Recipient email address and name */
    protected array $addresse = [];

    /** @var array List of BCC recipients */
    protected array $bccs = [];

    /** @var string Email subject line */
    protected string $subject;

    /** @var string Email body content */
    protected string $body;

    /** @var int SMTP port */
    protected int $port;

    /** @var string SMTP encryption method */
    protected string $secure;

    /** @var string SMTP host server */
    protected string $host;

    /** @var array List of processed file attachments */
    protected array $attachments = [];

    /** @var array Internal container for email state and debugging data */
    protected array $email_data = [];

    /**
     * Email constructor.
     * Initializes configuration values and triggers garbage collection of orphaned temp files.
     */
    public function __construct()
    {
        $this->sender['email'] = config('email.user');
        $this->email_data['sender'] = $this->sender;
        $this->password = config('email.password');
        $this->email_data['password'] = $this->password;
        $this->host = config('email.host');
        $this->email_data['host'] = $this->host;
        $this->secure = config('email.secure');
        $this->email_data['secure'] = $this->secure;
        $this->port = config('email.port');
        $this->email_data['port'] = $this->port;

        // Automatically clean up any orphaned temp files left behind from previous aborted requests
        $this->cleanup_orphaned_temp_files();
    }

    /**
     * Sets the sender name
     * @param string $name Sender name
     * @return self
     */
    public function sender_name(string $name): self
    {
        $this->sender['name'] = $name;
        $this->email_data['sender'] = $this->sender;
        return $this;
    }

    /**
     * Sets the recipient email address.
     *
     * @param string $email Recipient email
     * @param string $name Recipient name;
     * @return self
     */
    public function addressee(string $email, string $name = ''): self
    {
        $this->addresse['email'] = $email;
        $this->addresse['name'] = $name;
        $this->email_data['addressee'] = $this->addresse;
        return $this;
    }

    /**
     * Adds one or more BCC recipients.
     *
     * @param string|array $email Email address string or an array of emails/recipients
     * @param string $name Optional recipient name
     * @return self
     */
    public function bcc(string|array $email, string $name = ''): self
    {
        if (is_array($email)) {
            foreach ($email as $item) {
                if (is_array($item)) {
                    $this->bccs[] = [
                        'email' => $item['email'] ?? '',
                        'name'  => $item['name'] ?? ''
                    ];
                } else {
                    $this->bccs[] = [
                        'email' => $item,
                        'name'  => ''
                    ];
                }
            }
        } else {
            $this->bccs[] = [
                'email' => $email,
                'name'  => $name
            ];
        }

        $this->email_data['bccs'] = $this->bccs;
        return $this;
    }

    /**
     * Sets the email subject.
     *
     * @param string $subject Subject line
     * @return self
     */
    public function subject(string $subject): self
    {
        $this->subject = $subject;
        $this->email_data['subject'] = $this->subject;
        return $this;
    }

    /**
     * Sets the email body content.
     *
     * @param string $body Body text/HTML
     * @return self
     */
    public function body(string $body): self
    {
        $this->body = $body;
        $this->email_data['body'] = $this->body;
        return $this;
    }

    /**
     * Processes and stores multiple uploaded file attachments from $_FILES.
     *
     * @param array $files The $_FILES array chunk for attachments
     * @return self
     */
    public function attachments(array $files = []): self
    {
        if (!empty($files) && !empty($files['name'][0])) {
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $files['name'][$i],
                        'type' => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error' => $files['error'][$i],
                        'size' => $files['size'][$i]
                    ];

                    $processed = $this->process_attachments($file);
                    $this->attachments[] = $processed;
                    $this->email_data['attachments'][] = $processed;
                }
            }
        }
        return $this;
    }

    /**
     * Attaches a file from a specific server path without marking it for deletion.
     *
     * @param string $path Absolute or relative path to the file on the server
     * @param string $name Optional custom name for the attachment in the email
     * @return self
     */
    public function attachment_from_path(string $path, string $name = ''): self
    {
        if (!file_exists($path)) {
            throw new \InvalidArgumentException("Attachment file not found: {$path}");
        }

        $attachment = [
            'path'      => $path,
            'name'      => $name !== '' ? $name : basename($path),
            'temporary' => false // Prevents cleanup_attachments() from deleting app files
        ];

        $this->attachments[] = $attachment;
        $this->email_data['attachments'][] = $attachment;

        return $this;
    }

    /**
     * Moves an uploaded file into a safe temporary path using a custom identifiable prefix.
     *
     * @param array $file Single file array element
     * @return array Processed file metadata containing path and original name
     */
    protected function process_attachments(array $file): array
    {
        $name = $file['name'];
        $tmp_name = $file['tmp_name'];
        $extension = pathinfo($name, PATHINFO_EXTENSION);

        // Use a custom prefix 'zap_email_' so we can easily track and clean them up on Windows/Linux
        $safe_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'zap_email_' . uniqid('', true) . '.' . $extension;

        move_uploaded_file($tmp_name, $safe_path);

        return [
            'path' => $safe_path,
            'name' => $name,
            'temporary' => true
        ];
    }

    /**
     * Returns internal email state and debugging data.
     *
     * @return array
     */
    public function email_data(): array
    {
        return $this->email_data;
    }

    /**
     * Configures PHPMailer, attaches files, sends the email, and cleans up active temp files.
     *
     * @return array Execution status and message
     */
    public function execute(): array
    {
        $mailer = new PHPMailer(true);
        try {
            $mailer->isSMTP();
            $mailer->Host = $this->host;
            $mailer->SMTPAuth = true;
            $mailer->Username = $this->sender['email'];
            $mailer->Password = $this->password;
            $mailer->SMTPSecure = $this->secure;
            $mailer->Port = $this->port;

            if (config('app.environment') === 'development') {
                $mailer->SMTPDebug = 2;
                $mailer->Debugoutput = 'error_log';
            } else {
                $mailer->SMTPDebug = 0;
            }

            $mailer->setFrom($this->sender['email'], $this->sender['name'] ?? '');
            $mailer->addAddress($this->addresse['email'], $this->addresse['name'] ?? '');

            if(!empty($this->bccs)){
                foreach($this->bccs as $bcc){
                    if(!empty($bcc['email'])){
                        $mailer->addBCC($bcc['email'], $bcc['name'] ?? '');
                    }
                }
            }

            $mailer->isHTML(true);
            $mailer->Subject = $this->subject;
            $mailer->Body = $this->body;
            $mailer->Timeout = 10;

            if (!empty($this->attachments)) {
                foreach ($this->attachments as $attachment) {
                    if (file_exists($attachment['path'])) {
                        $mailer->addAttachment($attachment['path'], $attachment['name']);
                    }
                }
            }

            $mailer->send();
            $this->cleanup_attachments();

            return [
                'status' => 'success',
                'message' => 'Email has been sent.',
            ];
        } catch (Exception $e) {
            $this->email_data['error'] = $mailer->ErrorInfo;
            $this->cleanup_attachments();

            return [
                'status' => 'error',
                'message' => $mailer->ErrorInfo,
            ];
        }
    }

    /**
     * Removes temporary files explicitly tracked by the current instance after dispatch.
     *
     * @return void
     */
    protected function cleanup_attachments(): void
    {
        if (!empty($this->attachments)) {
            foreach ($this->attachments as $attachment) {
                if (!empty($attachment['temporary']) && isset($attachment['path']) && file_exists($attachment['path'])) {
                    @unlink($attachment['path']);
                }
            }
        }
    }

    /**
     * Scans the system temporary directory and removes any leftover/abandoned 
     * files containing 'zap_email_' that are older than 1 hour.
     *
     * @return void
     */
    protected function cleanup_orphaned_temp_files(): void
    {
        $tempDir = sys_get_temp_dir();
        $pattern = $tempDir . DIRECTORY_SEPARATOR . 'zap_email_*';
        $files = glob($pattern);

        if ($files) {
            $threshold = time() - 300;

            foreach ($files as $file) {
                // Only delete files older than 1 hour to prevent conflicts with active requests
                if (is_file($file) && filemtime($file) < $threshold) {
                    @unlink($file);
                }
            }
        }
    }
}
