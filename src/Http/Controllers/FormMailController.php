<?php

namespace Pbc\FormMail\Http\Controllers;

use App\Http\Controllers\Controller;

use DB;
use Illuminate\Http\Request;
use Pbc\Bandolier\Type\Encoded;
use Pbc\FormMail\FormMail;
use Pbc\Premailer;
use Response;
use Validator;

/**
 * Class FormMailController
 * @package Pbc\FormMail\Http\Controllers
 */
class FormMailController extends Controller
{
    /**
     * path to resources
     */
    const RESOURCE_ROOT = 'pbc_form_mail';

    /**
     * recipient key
     */
    const RECIPIENT = "recipient";

    /**
     * recipient value
     */
    const SENDER = "sender";

    /**
     * @var array
     */
    protected $rules = [
        'email' => 'required|email',
        'name' => 'required',
        'fields' => 'required|array'
    ];
    /**
     * @var Premailer
     */
    protected $premailer;

    /**
     * FormMailController constructor.
     * @param Premailer $premailer
     */
    public function __construct(Premailer $premailer, \Pbc\FormMail\Helpers\FormMailHelper $helper)
    {
        $this->premailer = $premailer;
        $this->helper = $helper;
        $this->rules = \FormMailHelper::prepRules();
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function requestHandler(Request $request, $data = [])
    {
        $return = [
            'queue' => config('form_mail.queue'),
            'confirmation' => config('form_mail.confirmation'),
        ];

        $validator = Validator::make($request->all(), $this->rules, []);
        if ($validator->fails()) {
            $return['error'] = $validator->errors()->all();
            return Response::json($return);
        }

        // make form name
        $this->helper
            ->formName($data)

            // create recipient from the form name and the current host
            ->recipient($data, \Route::currentRouteName())

            // set sender key
            ->sender($data, $request->input('email'))

            // path to resources, used for path to view and localization
            ->resource($data, __CLASS__, __FUNCTION__)

            // create fields list from the fields submitted to this handler
            ->fields($data, $request)

            // Email message subject
            ->subject($data)

            // branding string
            ->branding($data)

            // headline for return response
            ->head($data)

        /** @var string $response response that will be passed as success */
            ->response($data);


        // make record in formMail model
        DB::beginTransaction();
        try {
            $formMailModelData = [
                'form' => $data['formName'],
                'resource' => $data['resource'],
                'sender' => $data['sender'],
                'recipient' => $data['recipient'],
                'fields' => $data['fields'],
                'subject' => $data['subject'],
                'branding' => $data['branding'],
                'head' => $data['head'],
                'message_sent_to_recipient' => false,
                'confirmation_sent_to_sender' => false,
            ];
            $formMailModel = new FormMail($formMailModelData);
            $formMailModel->save();
            \FormMailHelper::messageToRecipient($formMailModel, $this->premailer);
            \FormMailHelper::messageToSender($formMailModel, $this->premailer);
        } catch (\Exception $ex) {
            // @codeCoverageIgnoreStart
            DB::rollBack();
            $return['error'] = [$ex->getMessage()];
            return Response::json($return);
            // @codeCoverageIgnoreEnd
        }
        DB::commit();
        // if    we should be queueing this message and confirmation,
        // then do that here, otherwise email out the messages
        // below.
        try {
            if (config('form_mail.queue')) {
                \FormMailHelper::queue($formMailModel, $this->premailer);
            } else {
                \FormMailHelper::send($formMailModel);
            }
            // return the response message as a success
            $return['success'] = [Encoded::getThingThatIsEncoded($data['response'], self::SENDER)];
        } catch (\Exception $ex) {
            $return['error'] = [$ex->getMessage()];
        }

        return Response::json($return);

    }

}
