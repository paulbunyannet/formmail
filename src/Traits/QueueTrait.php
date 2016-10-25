<?php

namespace Pbc\FormMail\Traits;

use Pbc\FormMail\FormMail;
use Pbc\FormMail\Jobs\FormMailSendMessage;
use Pbc\FormMail\Jobs\FormMailSendConfirmationMessage;

trait QueueTrait {


    /**
     * Queue the messages for sending on next queue process
     *
     * @param FormMail $formMailModel
     */
    public function queue(FormMail $formMailModel, \Pbc\Premailer $premailer, $defaultDelay=10)
    {
        $formMailSendMessage =  (new FormMailSendMessage($formMailModel, $premailer))->delay(config('form_mail.delay.send_message', $defaultDelay));
        $this->dispatch($formMailSendMessage);
        if (config('form_mail.confirmation')) {
            $formMailSendConfirmationMessage = (new FormMailSendConfirmationMessage($formMailModel, $premailer))->delay(config('form_mail.delay.send_confirmation', $defaultDelay));
            $this->dispatch($formMailSendConfirmationMessage);
        }
    }
}
