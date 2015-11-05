<?php

/**
 * @creator - Harish Kumar B P from GlyphWebCore Technologies
 * @website - http://www.magedevelopment.com
 * @module  - GlyphWebCore NewsletterTest
 *
**/

class GlyphWebCore_NewsletterTest_Adminhtml_SendtestController extends Mage_Adminhtml_Controller_Action
{	



    public function indexAction()
    {
		$this->loadLayout();
        $this->renderLayout();
    }
    
    
    

    public function postAction()
    {
        $post = $this->getRequest()->getPost();
        
        try 
        {
        	if(empty($post))
        	{
                Mage::throwException($this->__('Invalid form data.'));
            }
            
            $template = Mage::getModel('newsletter/template');
            $queue = Mage::getModel('newsletter/queue');
            $queue->load($post['id']);
            $template->setTemplateType($queue->getNewsletterType());
            $template->setTemplateText($queue->getNewsletterText());
            $template->setTemplateStyles($queue->getNewsletterStyles());
            $sender = $queue->getNewsletterSenderEmail();
            $subject = $queue->getNewsletterSubject();
            
        	$storeId = (int)$this->getRequest()->getParam('store_id');
        	if(!$storeId) 
        	{
            	$storeId = Mage::app()->getDefaultStoreView()->getId();
        	}           
            
        	Varien_Profiler::start("newsletter_queue_proccessing");
        	
        	$vars = array();
        	$vars['subscriber'] = Mage::getModel('newsletter/subscriber');

        	$template->emulateDesign($storeId);
        	$templateProcessed = $template->getProcessedTemplate($vars, true);
        	$template->revertDesign();

        	if($template->isPlain()) 
        	{
            	$templateProcessed = "<pre>" . htmlspecialchars($templateProcessed) . "</pre>";
        	}

        	Varien_Profiler::stop("newsletter_queue_proccessing");
            
            $mail = new Zend_Mail();
            $mail->setFrom($sender);
            $mail->setBodyHtml($templateProcessed);
            $mail->addTo($post['email'], $post['name']);
            $mail->setSubject($subject);
            $mail->send();            
            
            $message = $this->__("Newsletter sent to ".$post['email'].". Please check both your Inbox and Spam folders for the Newsletter Test Email.");
            Mage::getSingleton('adminhtml/session')->addSuccess($message);
        } 
        catch (Exception $e) 
        {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }         
        
        $this->_redirect('*/*/');
    }
    	
}