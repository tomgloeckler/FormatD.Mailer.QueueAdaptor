<?php

namespace FormatD\Mailer\QueueAdaptor\Job;

use Neos\Cache\Frontend\StringFrontend;
use Neos\Flow\Annotations as Flow;
use Flowpack\JobQueue\Common\Job\JobInterface;
use Flowpack\JobQueue\Common\Queue\QueueInterface;
use Flowpack\JobQueue\Common\Queue\Message;
use Neos\SymfonyMailer\Service\MailerService;
use Symfony\Component\Mime\Email;

class MailJob implements JobInterface {

    #[Flow\InjectConfiguration(path: 'serializationCache', package: 'FormatD.Mailer.QueueAdaptor')]
    protected array $serializationCacheSettings;

    #[Flow\Inject]
    protected Context $jobContext;

    #[Flow\Inject]
    protected StringFrontend $mailSerializationCache;

    #[Flow\Inject]
    protected MailerService $mailerService;

    protected ?Email $email = null;

    protected ?string $emailSerializationCacheIdentifier = null;

    public function __construct(Email $email) {
        $this->email = $email;
    }

    /**
     * Execute the job
     * A job should finish itself after successful execution using the queue methods.
     */
    public function execute(QueueInterface $queue, Message $message): bool {

        $message = $this->getEmail();

        $this->jobContext->withoutMailQueuing(function() use ($message) {
            $this->mailerService->getMailer()->send($message);
        });

        return true;
    }

    public function getLabel(): string {
        return $this->getEmail()->getSubject();
    }

    /**
     * Serialize the email to a file because it can get really big with attachments
     *
     * @return string[]
     * @throws \Neos\Cache\Exception
     * @throws \Neos\Cache\Exception\InvalidDataException
     */
    public function __sleep()
    {
        if ($this->serializationCacheSettings['enabled']) {
            $this->emailSerializationCacheIdentifier = uniqid('email-');
            $this->mailSerializationCache->set($this->emailSerializationCacheIdentifier, serialize($this->email), [], 172800); // 48 Std. lifetime
            return array('emailSerializationCacheIdentifier');
        }

        return array('email');
    }

    /**
     * Restores the serialized email if cached in file
     */
    protected function getEmail(): Email {
        if (!$this->email && $this->emailSerializationCacheIdentifier && $this->mailSerializationCache->has($this->emailSerializationCacheIdentifier)) {
            $this->email = unserialize($this->mailSerializationCache->get($this->emailSerializationCacheIdentifier));
        }
        return $this->email;
    }

}
