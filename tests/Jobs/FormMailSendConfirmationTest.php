<?php
namespace Pbc\FormMail\Tests\Jobs;

/**
 * FormMailSendMessageTest
 *
 * Created 6/23/16 8:01 AM
 * Tests for the FormMailSendMessage job class
 *
 * @author Nate Nolting <naten@paulbunyan.net>
 * @package Pbc\FormMail\Tests\Jobs
 */

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Pbc\FormMail\Jobs\FormMailSendConfirmationMessage;

class FormMailSendConfirmationTest extends \TestCase
{

    use DatabaseTransactions;

    public function setup()
    {
        parent::setUp();

    }

    public function tearDown()
    {
        parent::tearDown();
        Mockery::close();
    }

    /**
     * Job does nothing if confirmation_sent_to_sender is set to true
     * @test
     */
    public function the_handle_does_nothing_if_confirmation_sent_to_sender_is_true()
    {
        $formMail = Mockery::mock('Pbc\FormMail\FormMail');
        $this->app->instance('Pbc\FormMail\FormMail', $formMail);
        $formMail->shouldReceive('getAttribute')->zeroOrMoreTimes()->with('confirmation_sent_to_sender')->andReturn(true);

        $premailerMock = Mockery::mock('\\Pbc\\Premailer');

        $job = new FormMailSendConfirmationMessage($formMail, $premailerMock);
        $this->assertNull($job->handle());
    }

    /**
     * Job does nothing if confirmation is not set to true
     * 
     * @test
     */
    public function the_handle_does_nothing_if_confirmation_is_set_to_false()
    {
        $formMailMock = Mockery::mock('Pbc\FormMail\FormMail');
        $this->app->instance('Pbc\FormMail\FormMail', $formMailMock);
        $formMailMock->shouldReceive('getAttribute')->zeroOrMoreTimes()->with('confirmation_sent_to_sender')->andReturn(false);
        \Config::set('form_mail.confirmation', false);

        $premailerMock = Mockery::mock('\\Pbc\\Premailer');

        $job = new FormMailSendConfirmationMessage($formMailMock, $premailerMock);
        $this->assertNull($job->handle());
    }
    
    /**
     * Test that handle will throw an exception if the html and
     * text keys are missing in message_to_sender
     *
     * @test
     * @expectedException \Exception
     * @expectedExceptionMessage Missing html and/or text keys in message_to_sender
     */
    public function the_handle_throws_an_exception_if_html_and_text_keys_are_missing()
    {
        $value = ['key1' => 'bla bla bla'];
        $formMailMock = Mockery::mock('Pbc\FormMail\FormMail');

        $this->app->instance('Pbc\FormMail\FormMail', $formMailMock);
        $formMailMock->shouldReceive('setAttribute');

        $formMailMock->shouldReceive('getAttribute')->zeroOrMoreTimes()->with('message_to_sender')->andReturn($value);
        $formMailMock->shouldReceive('getAttribute')->zeroOrMoreTimes()->with('confirmation_sent_to_sender')->andReturn(false);
        $formMailMock->shouldReceive('save')->zeroOrMoreTimes()->andReturn(true);
        \Config::set('form_mail.confirmation', true);

        $premailerMock = Mockery::mock('\\Pbc\\Premailer');
        $premailerMock->shouldReceive('html')->once()->andReturn(['html' => 'bla bla html', 'text' => 'bla bla bla text']);

        $job = new FormMailSendConfirmationMessage($formMailMock, $premailerMock);
        $job->handle();
    }

    /**
     * Check that handle will throw an exception if the
     * recipient is an invalid email address
     *
     * @expectedException \Exception
     * @expectedExceptionMessage Invalid recipient address
     * @test
     */
    public function the_handle_will_throw_an_exception_if_recipient_is_not_an_email_address()
    {
        $faker = \Faker\Factory::create();
        $formMailMock = Mockery::mock('Pbc\FormMail\FormMail');
        $this->app->instance('Pbc\FormMail\FormMail', $formMailMock);

        $formMailMock->shouldReceive('setAttribute');
        $formMailMock->shouldReceive('save');

        $formMailMock->shouldReceive('getAttribute')->zeroOrMoreTimes()->with('confirmation_sent_to_sender')->andReturn(false);
        \Config::set('form_mail.confirmation', true);
        $html = ['html' => 'bla bla bla'];
        $formMailMock->shouldReceive('getAttribute')->zeroOrMoreTimes()->with('message_to_sender')->andReturn($html);

        $recipient = implode($faker->words(3));
        $formMailMock->shouldReceive('getAttribute')->zeroOrMoreTimes()->with('recipient')->andReturn($recipient);

        $sender = $faker->email;
        $formMailMock->shouldReceive('getAttribute')->zeroOrMoreTimes()->with('sender')->andReturn($sender);

        $subject = $faker->sentence();
        $formMailMock->shouldReceive('getAttribute')->zeroOrMoreTimes()->with('subject')->andReturn($subject);

        $premailerMock = Mockery::mock('\\Pbc\\Premailer');
        $premailerMock->shouldReceive('html')->once()->andReturn(['html' => 'bla bla html', 'text' => 'bla bla bla text']);


        $job = new FormMailSendConfirmationMessage($formMailMock, $premailerMock);
        $job->handle();
    }


    /**
     * Check that handle will throw an exception if the
     * recipient is an invalid email address
     *
     * @expectedException \Exception
     * @expectedExceptionMessage Invalid sender address
     * @test
     */
    public function the_handle_will_throw_an_exception_if_sender_is_not_an_email_address()
    {
        $faker = \Faker\Factory::create();
        $formMailMock = Mockery::mock('Pbc\FormMail\FormMail');
        $this->app->instance('Pbc\FormMail\FormMail', $formMailMock);

        $formMailMock->shouldReceive('setAttribute');
        $formMailMock->shouldReceive('save');

        $formMailMock->shouldReceive('getAttribute')->zeroOrMoreTimes()->with('confirmation_sent_to_sender')->andReturn(false);
        \Config::set('form_mail.confirmation', true);

        $html = ['html' => 'bla bla bla'];
        $formMailMock->shouldReceive('getAttribute')->zeroOrMoreTimes()->with('message_to_sender')->andReturn($html);

        $recipient = $faker->email;
        $formMailMock->shouldReceive('getAttribute')->zeroOrMoreTimes()->with('recipient')->andReturn($recipient);

        $sender = implode($faker->words(3));
        $formMailMock->shouldReceive('getAttribute')->zeroOrMoreTimes()->with('sender')->andReturn($sender);

        $subject = $faker->sentence();
        $formMailMock->shouldReceive('getAttribute')->zeroOrMoreTimes()->with('subject')->andReturn($subject);

        $premailerMock = Mockery::mock('\\Pbc\\Premailer');
        $premailerMock->shouldReceive('html')->once()->andReturn(['html' => 'bla bla html', 'text' => 'bla bla bla text']);


        $job = new FormMailSendConfirmationMessage($formMailMock, $premailerMock);
        $job->handle();
    }

    /**
     * Check that handle will not throw exception if:
     * 1. html key are present in message_sent_to_recipient
     * 2. recipient email address is valid
     * 3. sender email address is valid
     *
     * @test
     */
    public function the_handle_does_not_throw_an_exception_if_validations_pass()
    {
        $faker = \Faker\Factory::create();
        $formMail = Mockery::mock('Pbc\FormMail\FormMail');
        $this->app->instance('Pbc\FormMail\FormMail', $formMail);

        $formMail->shouldReceive('setAttribute');
        $formMail->shouldReceive('save');

        $formMail->shouldReceive('getAttribute')->zeroOrMoreTimes()->with('confirmation_sent_to_sender')->andReturn(false);
        \Config::set('form_mail.confirmation', true);

        $html = ['html' => 'bla bla bla'];
        $formMail->shouldReceive('getAttribute')->zeroOrMoreTimes()->with('message_to_sender')->andReturn($html);

        $recipient = $faker->email;
        $formMail->shouldReceive('getAttribute')->zeroOrMoreTimes()->with('recipient')->andReturn($recipient);
        $formMail->recipient = $recipient;

        $sender = $faker->email;
        $formMail->shouldReceive('getAttribute')->zeroOrMoreTimes()->with('sender')->andReturn($sender);
        $formMail->sender = $sender;

        $subject = $faker->sentence();
        $formMail->shouldReceive('getAttribute')->zeroOrMoreTimes()->with('subject')->andReturn($subject);
        $formMail->subject = $subject;

        // see http://stackoverflow.com/a/31135826 for example
        \Mail::shouldReceive('failures')->zeroOrMoreTimes()->andReturn([]);
        \Mail::shouldReceive('send')->once()->with(
            'pbc_form_mail_template::body',
            \Mockery::on(function ($data) use ($formMail) {
                $this->assertArrayHasKey('data', $data);
                $this->assertSame($data['data'], $formMail->message_to_sender);
                return true;
            }),
            \Mockery::on(function (\Closure $closure) use ($formMail) {

                $mock = Mockery::mock('Illuminate\Mailer\Message');
                // mock to method
                $mock->shouldReceive('to')
                    ->once()
                    ->with($formMail->sender)
                    ->andReturn($mock); //simulate the chaining
                // mock from method
                $mock->shouldReceive('from')
                    ->once()
                    ->with($formMail->recipient)
                    ->andReturn($mock); //simulate the chaining
                // mock subject method
                $mock->shouldReceive('subject')
                    ->once()
                    ->with($formMail->subject);
                $closure($mock);
                return true;

            })
        );

        $premailerMock = Mockery::mock('\\Pbc\\Premailer');
        $premailerMock->shouldReceive('html')->once()->andReturn(['html' => 'bla bla html', 'text' => 'bla bla bla text']);

        $job = new FormMailSendConfirmationMessage($formMail, $premailerMock);
        $this->assertNull($job->handle());
    }
    
}