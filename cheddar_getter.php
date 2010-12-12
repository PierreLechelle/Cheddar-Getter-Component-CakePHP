<?
/**
 * Copyright 2009-2011, You Exist (http://www.you-exist.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2009-2011, You Exist (http://www.you-exist.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author Pierre Lechelle
 */
 
class CheddarGetterComponent extends Object
{
    var $_auth = array(
        "user" => "",
        "pass" => ""
    );
    
    function initialize(&$controller, $settings = array())
    {        
        if (!empty($settings['auth'])) 
        {
            $this->_auth = $settings['auth'];
        }
        else if(Configure::read('CheddarGetter.auth') !== false)
        {
            $this->_auth = Configure::read('CheddarGetter.auth');
        }
    }
    
    function addItemQuantity($product_code, $customer_code, $item_code, $data = false)
    {
        return $this->_call("customers/add-item-quantity/productCode/$product_code/code/$customer_code/itemCode/$item_code", $data, "post");
    }
    
    function removeItemQuantity($product_code, $customer_code, $item_code, $data = false)
    {
        return $this->_call("customers/remove-item-quantity/productCode/$product_code/code/$customer_code/itemCode/$item_code", $data, "post");
    }
    
    function setItemQuantity($product_code, $customer_code, $item_code, $data = false)
    {
        return $this->_call("customers/add-item-quantity/productCode/$product_code/code/$customer_code/itemCode/$item_code", $data, "post");
    }
    
    function viewCustomer($customer_id, $product_code)
    {
        return $this->_call("customers/get/productCode/$product_code/code/$customer_id");
    }
    
    function updateCustomerAndSubscription($customer_code, $product_code, $data)
    {
        return $this->_call("customers/edit/productCode/$product_code/code/$customer_code", $data, "post");
    }
    
    function updateCustomer($customer_code, $product_code, $data)
    {
        return $this->_call("customers/edit-customer/productCode/$product_code/code/$customer_code", $data, "post");
    }
    
    function updateSubscription($customer_code, $product_code, $data)
    {
        return $this->_call("customers/edit-subscription/productCode/$product_code/code/$customer_code", $data, "post");
    }
    
    function deleteCustomer($customer_code, $product_code)
    {
        return $this->_call("customers/edit/productCode/$product_code/code/$customer_code");
    }
    
    function deleteAllCustomers($product_code)
    {
        return $this->_call("customers/delete-all/confirm/1/productCode/$product_code");
    }
    
    function getCustomers($product_code)
    {
        return $this->_call("customers/get/productCode/$product_code");
    }
    
    function addCustomer($data, $product_code)
    {
        return $this->_call("customers/new/productCode/$product_code", $data);
    }
    
    function _call($url, $options = array(), $protocol = "post")
    {
        App::import('Core', array('HttpSocket', 'Xml'));
        $HttpSocket = new HttpSocket();
        
        if ($protocol == "post") {
            $response = $HttpSocket->post('https://cheddargetter.com/xml/'.$url, $options, array(
                'auth' => $this->_auth
            ));
        } else if ($protocol == "get") {
            $response = $HttpSocket->get('https://cheddargetter.com/xml/'.$url, $options, array(
                'auth' => $this->_auth
            ));            
        }
        
        $Xml = new Xml($response);   
        $response_array = $Xml->toArray();
        
        if (array_key_exists('error', $response_array)) 
        {
            if($response_array['error']['code'] == 400) 
            {
                throw new Exception(__("Requête invalide", true));
            } 
            else if ($response_array['error']['code'] == 401) 
            {
                if ($response_array['error']['auxCode'] == 2000) 
                {
                    throw new Exception(__("Probléme d'authentification à l'API.", true));
                }
                else if ($response_array['error']['auxCode'] == 2001)
                {
                    throw new Exception(__("Probléme de configuration sur la plate forme de paiement.", true));
                }
                else if ($response_array['error']['auxCode'] == 2002)
                {
                    throw new Exception(__("Probléme d'authentification sur la plate forme de paiement.", true));
                }
                else if ($response_array['error']['auxCode'] == 2003)
                {
                    throw new Exception(__("Accès non authorisé sur la plate forme de paiement.", true));
                }
                throw new Exception(__('Requête non authorisée', true));
            } 
            else if ($response_array['error']['code'] == 404) 
            {
                throw new Exception(__('Ressource introuvable', true));
            } 
            else if ($response_array['error']['code'] == 412) 
            {
                if ($response_array['error']['auxCode'] == "subscription[ccExpiration]:not_future") 
                {
                    throw new Exception(__("La date que vous avez saisie pour l'expiration de votre carte bancaire se trouve dans le passé.", true));
                }
                else if ($response_array['error']['auxCode'] == "code:CustomerCodeNotUnique")
                {
                    throw new Exception(__("Vous êtes déjà inscrit à notre organisme de paiement.", true));
                }
                else
                {
                    throw new Exception(__('Erreur durant le processus', true));
                }
            } 
            else if ($response_array['error']['code'] == 422) 
            {
                if ($response_array['error']['auxCode'] == "subscription[ccExpiration]:not_future") 
                {
                    throw new Exception(__("La date que vous avez saisie pour l'expiration de votre carte bancaire se trouve dans le passé.", true));
                }
                else if ($response_array['error']['auxCode'] == "5001")
                {
                    throw new Exception(__("Le numéro de carte de crédit saisi est invalide.", true));
                }
                else if ($response_array['error']['auxCode'] == "5002")
                {
                    throw new Exception(__("La date d'expiration saisie est invalide.", true));
                }
                else if ($response_array['error']['auxCode'] == "5003")
                {
                    throw new Exception(__("Ce type de carte de crédit n'est pas accepté.", true));
                }
                else if ($response_array['error']['auxCode'] == "6000")
                {
                    throw new Exception(__("La transaction a été refusée par votre organisme bancaire.", true));
                }
                else if ($response_array['error']['auxCode'] == "6001")
                {
                    throw new Exception(__("Le code postal saisi est invalide.", true));
                }
                else if ($response_array['error']['auxCode'] == "6002")
                {
                    throw new Exception(__("Le cryptogramme visuel de votre carte de crédit saisi est invalide.", true));
                }
                else
                {
                    throw new Exception(__('Erreur dans les données', true));
                }
            }
            else if ($response_array['error']['code'] == 502)
            {
                if ($response_array['error']['auxCode'] == 3000)
                {
                    throw new Exception(__("Réponse invalide de la plate forme de paiement.", true));
                }
                else if ($response_array['error']['auxCode'] == 4000)
                {
                    throw new Exception(__("Probléme lors de la connexion à la plate forme de paiement.", true));
                }
                else
                {
                    throw new Exception(__('Probléme interne au serveur de paiement', true));
                }   
            } 
            else if ($response_array['error']['code'] == 500) 
            {
                throw new Exception(__('Probléme interne au serveur de paiement', true));
            }
        }
           
        return $response_array;
    }
}
