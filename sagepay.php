<?php
define('BILLING',0);
define('DELIVERY',1);
/**
 * SagePay
 *
 * Utility class for preparing data to send to SagePay and receiving data from SagePay.  Created as a more re-usable version of the SagePay example integration code.
 *
 */
class Sagepay {
    
    private $mode = 'SIMULATOR';
    public $url = 'https://live.sagepay.com/gateway/service/vspform-register.vsp';
    private $testUrl = 'https://test.sagepay.com/gateway/service/vspform-register.vsp';
    private $liveUrl = 'https://live.sagepay.com/gateway/service/vspform-register.vsp';
    private $simUrl = 'https://test.sagepay.com/simulator/vspformgateway.asp';
    private $FQDN = 'http://wigs.fractalwebdesign.co.uk';
    public $vendor = 'e';
    private $encryptionPassword = '';
    private $currency = 'GBP';
    private $transactionType = 'PAYMENT';
    
    public $orderId;
    public $sendEmail = 2;
    public $amount = 0;
    
    public $data = '';
    public $billingAddressString = '';
    public $deliveryAddressString = '';
    
    
    /**
     * Encrypts an array of data for use as the "crypt" field for SagePay
     *
     * @param array $data Array of data per SagePay integration manual
     * @return string Encrypted data ready to send to SagePay
     * @access private
     */
    private function encrypt($data){
        
        $strIV = $this->encryptionPassword;
        $data = $this->addPKCS5Padding($data);
        $encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $this->encryptionPassword, $data, MCRYPT_MODE_CBC, $strIV);
        return '@' . bin2hex($encrypted);
    }
    
    /**
     * Decrypts an encrypted response from SagePay
     *
     * @param string $data Encrypted data string
     * @return string Decrypted data string
     * @access public
     */
    public function decrypt($data){
        $strIV = $this->encryptionPassword;
        $data = substr($data,1); 
        $data = pack('H*', $data);
        return mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $this->encryptionPassword, $data, MCRYPT_MODE_CBC, $strIV); 
    }
    
    private function addPKCS5Padding($data){
        $blocksize = 16;
        $padding = "";

        $padlength = $blocksize - (strlen($data) % $blocksize);
        for($i = 1; $i <= $padlength; $i++) {
          $padding .= chr($padlength);
        }
       return $data . $padding;
    }
    
    /**
     * Set the billing or delivery address
     *
     * @param array $address Array of address data per SagePay integration manual
     * @param boolean $addressType true if delivery address, false if billing address
     * @return string Encrypted data ready to send to SagePay
     * @access public
     */
    public function setAddress($address, $addressType){
        $output = '';
        if ($addressType == BILLING){
            $prefix = 'Delivery';
        }else{
            $prefix = 'Billing';
            $output .= '&BillingPhone=' . $address['phone'];
        }
        $output .= '&' . $prefix . 'Surname=' . $address['last_name']
                . '&' . $prefix . 'Firstnames=' . $address['first_name']
                . '&' . $prefix . 'Address1=' . $address['address_1']
                . '&' . $prefix . 'Address2=' . $address['address_2']
                . '&' . $prefix . 'City=' . $address['city']
                . '&' . $prefix . 'PostCode=' . $address['postcode']
                . '&' . $prefix . 'Country=GB';
        if ($addressType == BILLING){
            $this->billingAddressString = $output;
        }else{
            $this->deliveryAddressString = $output;
        }
    }
    
    /**
     * Create data string from array
     *
     * @param array $data Array of data per SagePay integration manual
     * @return string data ready to send to SagePay
     * @access public
     */
    public function setData($data){
        $data['Currency'] = 'GBP';
        $data['SendEMail'] = $this->sendEmail;
        $this->data = '';
        foreach($data as $key=>$item){
            $this->data .= '&' . $key . '=' . $item;
        }
        $this->data = substr($this->data,1);
    }
    
    /**
     * Checks for problems and then assembles the data to be encrypted
     *
     * @return string Final data string ready for encryption
     * @access public
     */
    public function assembleData(){
        if (empty($this->billingAddressString)){
            throw new Exception('No billing address');
        }
        if (empty($this->deliveryAddressString)){
            throw new Exception('No delivery address');
        }
        if (empty($this->data)){
            throw new Exception('No order data');
        }
        $this->finalString = $this->data . $this->billingAddressString . $this->deliveryAddressString;
        return $this->finalString;
    }
    
    /**
     * Once data has been set, getCrypt() returns the full "crypt" field containing all of the transaction data for SagePay.
     *
     * @return string Encrypted data ready to send to SagePay
     * @access public
     */
    public function getCrypt(){
        return $this->encrypt($this->assembleData());
    }
}
?>