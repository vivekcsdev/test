<?php
class ReferralController extends Controller {
    public function __construct($request)
    {
        parent::__construct($request);
        $this->setActiveIconMenu('affiliate');

    }
    public function index() {
        $this->model('referral::referral')->ensureAffiliate();

        if ($val = $this->request->input('val')) {

            if ($val['action'] == 'payout') {
                model('referral::referral')->savePayout($val);
                return json_encode(array(
                    'type' => 'function',
                    'message' => l('referral::payout-settings-saved')
                ));
            }
        }

        if ($action = $this->request->input('action')) {
            if ($action == 'mark' and $this->model('user')->isAdmin()) {
                model('referral::referral')->markPaid($this->request->input('id'));
                return json_encode(array(
                    'type' => 'url',
                    'value' => url('referral'),
                    'message'=> l('referral::payout-mark-paid')
                ));
            }elseif($action == 'pay' and $this->model('user')->isAdmin()) {
                require_once path('module/referral/paypal/vendor/autoload.php');

                $massPayRequest = new \PayPal\PayPalAPI\MassPayRequestType();
                $massPayRequest->MassPayItem = array();
                foreach(model('referral::referral')->getPendingPayouts() as $payout) {
                    $masspayItem = new \PayPal\PayPalAPI\MassPayRequestItemType();
                    $masspayItem->Amount = new \PayPal\CoreComponentTypes\BasicAmountType('USD', $payout['amount']);
                    $thReferral = model('referral::referral')->getDetails($payout['userid']);
                    $masspayItem->ReceiverEmail = $thReferral['paypal_email'];
                    $massPayRequest->MassPayItem[] = $masspayItem;
                }

                $masspayItem = new \PayPal\PayPalAPI\MassPayRequestItemType();
                $masspayItem->Amount = new \PayPal\CoreComponentTypes\BasicAmountType('USD', 20);
                $masspayItem->ReceiverEmail = 'tiamiyuwaliu@gmail.com';
                $massPayRequest->MassPayItem[] = $masspayItem;

                $massPayReq = new \PayPal\PayPalAPI\MassPayReq();
                $massPayReq->MassPayRequest = $massPayRequest;
                $paypalService = new \PayPal\Service\PayPalAPIInterfaceServiceService(Configuration::getAcctAndConfig());
                try {
                    $massPayResponse = $paypalService->MassPay($massPayReq);
                } catch (Exception $e) {
                    print_r($e);
                    exit;
                }
                return json_encode(array(
                    'type' => 'url',
                    'value' => url('referral'),
                    'message'=> l('referral::payout-mark-paid')
                ));
            }
        }

        return $this->render($this->view('referral::index'), true);
    }
}

class Configuration
{
    // For a full list of configuration parameters refer in wiki page (https://github.com/paypal/sdk-core-php/wiki/Configuring-the-SDK)
    public static function getConfig()
    {
        $config = array(
                       "mode" => "live",

            'log.LogEnabled' => true,
            'log.FileName' => '../PayPal.log',
            'log.LogLevel' => 'FINE'

        );
        return $config;
    }

    // Creates a configuration array containing credentials and other required configuration parameters.
    public static function getAcctAndConfig()
    {
        $config = array(
            // Signature Credential
            "acct1.UserName" => "socialrrific_api1.gmail.com",
            "acct1.Password" => "ZUXAQ2WNL5VHHBQR",
            "acct1.Signature" => "Aq8Ks74Hin1lPZQbkNk6Su8BmoBgArNG-9M.DMOe4pbYFrzWLuI9cS.w",


        );

        return array_merge($config, self::getConfig());
    }
}