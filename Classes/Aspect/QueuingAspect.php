<?php
namespace FormatD\Mailer\QueueAdaptor\Aspect;

/*                                                                        *
 * This script belongs to the Flow package "FormatD.Mailer.QueueAdaptor". *
 *                                                                        */

use FormatD\Mailer\QueueAdaptor\Job\Context;
use FormatD\Mailer\QueueAdaptor\Job\MailJob;
use Flowpack\JobQueue\Common\Job\JobManager;
use Neos\Flow\Annotations as Flow;
use Neos\SwiftMailer\Message;
use Symfony\Component\Mime\Email;


/**
 * @Flow\Aspect
 * @Flow\Introduce("class(Neos\SwiftMailer\Message)", traitName="FormatD\Mailer\QueueAdaptor\Traits\QueueNameTrait")
 */
class QueuingAspect {

	/**
	 * @var JobManager
	 * @Flow\Inject
	 */
	protected $jobManager;

	/**
	 * @Flow\Inject
	 * @var Context
	 */
	protected $jobContext;

	/**
	 * @Flow\InjectConfiguration
	 * @var array
	 */
	protected $settings;

	/**
	 * Intercept all emails or add bcc according to package configuration
	 *
	 * @param \Neos\Flow\Aop\JoinPointInterface $joinPoint
	 * @Flow\Around("setting(FormatD.Mailer.QueueAdaptor.enableAsynchronousMails) && method(Symfony\Component\Mailer\MailerInterface->send())")
	 * @return mixed
	 */
	public function queueEmails(\Neos\Flow\Aop\JoinPointInterface $joinPoint): mixed {

		if ($this->jobContext->isMailQueueingDisabled()) {
			return $joinPoint->getAdviceChain()->proceed($joinPoint);
		}

		/** @var Email $email */
		$email = $joinPoint->getMethodArgument('message');
		$job = new MailJob($email);
		$this->jobManager->queue($this->settings['queueName'], $job);

		return 1;
	}

}
