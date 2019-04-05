<?php

namespace WHMCS\Module\Addon\francedns\Admin;

require ROOTDIR . '/includes/functions.php';
require ROOTDIR . '/includes/registrarfunctions.php';
if(!$ClassIDN2)include("idn.class.php");

define ("API_SRC","whmcs");

use WHMCS\Database\Capsule;


/**
 * Admin Area Controller for FranceDNS
 */

/* gets the data from a URL */
function get_data($url) {
	$ch = curl_init();
	$timeout = 5;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	$data = curl_exec($ch);
	curl_close($ch);
	return $data;
} 
 
class Controller {
	
	/**
	 *  Get Price of a TLD with FranceDNS API
	 *
	 * @return array
	 */
	public function getDomainPrice($params, $domain) {
		$idn = new idna_convert2();
		$clientSOAP = new \SoapClient($params["API"]);
		$username = $params["Username"];
		$password = $params["Password"];
		$domainIDN = $idn->encode($domain);
		
		# ----------------------------------------------------------------------
		# Connection to the francedns's API
		# ----------------------------------------------------------------------
		try
		{
			//API Call
			$api_params=array($username, $password,"EN",API_SRC);
			$IDSession = $clientSOAP->__soapCall("sessionOpen",$api_params);
		}
		catch(SoapFault $fault)
		{
			$values["error"] = $fault->getMessage();
			return $values;
		}
		
		# ----------------------------------------------------------------------
		# Call to queryDomainPrice
		# ----------------------------------------------------------------------
		try
		{
			//API Call
			$api_params=array($IDSession, $domainIDN);
			$queryDomainPrice = $clientSOAP->__soapCall("queryDomainPrice",$api_params) ;
		}
		catch(SoapFault $fault)
		{
			$values["error"] = $fault->getMessage();
			$clientSOAP->__soapCall("sessionClose",array($IDSession)) ;
			return $values;
		}
		
		# ----------------------------------------------------------------------
		# Getting Price
		# ----------------------------------------------------------------------
		$values["FeeCurrency"] = $queryDomainPrice->FeeCurrency;
		$values["Fee4Registration"] = $queryDomainPrice->Fee4Registration;
		$values["Fee4Renewal"] = $queryDomainPrice->Fee4Renewal;
		$values["Fee4Transfer"] = $queryDomainPrice->Fee4Transfer;
		$values["Fee4Trade"] = $queryDomainPrice->Fee4Trade;
		$values["Fee4Restore"] = $queryDomainPrice->Fee4Restore;
		$values["Fee4TrusteeService"] = $queryDomainPrice->Fee4TrusteeService;
		$values["Fee4LocalContactService"] = $queryDomainPrice->Fee4LocalContactService;
		$values["IsPremium"] = $queryDomainPrice->IsPremium;
		
		# ----------------------------------------------------------------------
		# disconnection from the francedns's API
		# ----------------------------------------------------------------------
		if(isset($IDSession))
			$clientSOAP->__soapCall("sessionClose",array($IDSession)) ;
		
		return $values;
	}	

	/**
	 *  Get the TLD list from FranceDNS
	 */
	private function getTldList(){
		$input_lines = get_data("https://www.francedns.com/revendeur/tarifs.html");
		
		preg_match_all("/>(\.\w*)<\/a>/", $input_lines, $output_array);
		
		return $output_array[1];
	}
	
	/**
	 *  Get Price with french VAT for a TLD in BDD with FranceDNS API
	 *
	 * @return array( exts, local, fdns )
	 */
	private function getPrices(){
		// Get module configuration parameters
		$params = getregistrarconfigoptions ( 'francedns' );
		
		$pdo = Capsule::connection()->getPdo();
		$pdo->beginTransaction();
		$statement = $pdo->query(  "SELECT 
										d.`id`, 
										d.`extension`,
										p.`id` as price_id,
										p.`type`,
										p.`msetupfee`
									FROM `tbldomainpricing` d 
									LEFT JOIN `tblpricing` p on
										p.`relid`=d.`id` and 
										p.`type` like 'domain%'
									WHERE
										d.`autoreg`='francedns'
									ORDER by
										d.`extension`");
		$lastext = "";
		$francednsprices = array();
		$localprices = array();
		$exts = array();
		while($data = $statement->fetch()){
			$ext = $data['extension'];
			if($ext != $lastext) {
				$prices = $this->getDomainPrice($params,"test".$ext);
				$exts[] = $ext;
				$francednsprices[$ext] = array(
					"register" => $prices['Fee4Registration']*1.2,
					"transfer" => $prices['Fee4Transfer']*1.2,
					"renew" => $prices['Fee4Renewal']*1.2,
				);
				$lastext=$ext;
			}

			$actualPrice = $data["msetupfee"] * 1.0;
			if($data["type"] == "domainregister") {
				$localprices[$ext]["register"] = $actualPrice;
			}
			if($data["type"] == "domaintransfer"){
				$localprices[$ext]["transfer"] = $actualPrice;
			}
			if($data["type"] == "domainrenew"){
				$localprices[$ext]["renew"] = $actualPrice;
			}
			
		}
		$pdo->commit();
		return array(
			"exts" => $exts,
			"local" => $localprices,
			"fdns" => $francednsprices,
		);
	}
	
	/**
	 *  Show the page
	 */
	private function afficherPage($vars){
        // Get common module parameters
        $modulelink = $vars['modulelink']; // eg. addonmodules.php?module=addonmodule
        $version = $vars['version']; // eg. 1.0
        $LANG = $vars['_lang']; // an array of the currently loaded language variables
		$marge = $vars['marge'];
		$arrondir = $vars['arrondir'];
		$result = $vars['result'];
		        
		//$apiget = localAPI("francedns", array("action" => "getlocalprices"), "marbas");
		//var_dump($apiget);
				
		$prices = $this->getPrices();
		$exts = $prices['exts'];
		$francednsprices = $prices['fdns'];
		$localprices = $prices['local'];
		
		$tab = $this->getTldList();
		asort($tab);
		$tab = array_diff($tab, $exts);

		// create object
		$smarty = new \Smarty;

		// Example assign variable
		$smarty->assign('modulelink', $modulelink);
		$smarty->assign('francednsprices', $francednsprices);
		$smarty->assign('localprices', $localprices);
		$smarty->assign('marge',$marge);
		$smarty->assign('arrondir',$arrondir);
		$smarty->assign('result',$result);
		$smarty->assign('vars',$vars);
		$smarty->assign('exts', $exts);
		$smarty->assign('exts_fdns', $tab);

		$smarty->caching = false;

		$smarty->compile_dir = $GLOBALS['templates_compiledir'];

		// display file from template folder within add-on folder
		$smarty->display(dirname(__FILE__) . '/../../templates/importpage.tpl');
	}
	
    /**
     * Index action.
     *
     * @param array $vars Module configuration parameters
     *
     * @return string
     */
    public function index($vars) {
		$this->afficherPage($vars);
    }
	
    /**
     * Update the price.
     *
     * @param int $id Id of the price to update
     * @param float $price New price
     *
     * @return boolean
     */
	private function updatePrice($id, $price){
		try {
			$pdo = Capsule::connection()->getPdo();
			$pdo->beginTransaction();
			$statement = $pdo->prepare( "update `tblpricing` set `msetupfee`=:price where `id`=:id");
			$statement->execute( [':price' => $price,
								   ':id' => $id,
								 ]);
			$pdo->commit();
			
			return true;
		} catch (\Exception $e) {
			echo "<div class='alert alert-danger alert-dismissable'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><strong><span class='title'>Mise à jour de tarifs :</span></strong> {$e->getMessage()}</div>";
			$pdo->rollback();
			return false;
		}
	}
	
    /**
     * Insert the price.
     *
     * @param int $id Id of the tld
     * @param int $currencyId Id of the currency
     * @param int $type type of price : domainresgister, domaintransfer, domainrenewal etc
     * @param float $price New price
     *
     * @return boolean
     */
	private function insertPrice($id, $currencyId, $type, $price){
		try {
			$pdo = Capsule::connection()->getPdo();
			$pdo->beginTransaction();
			$statement = $pdo->prepare( "insert into `tblpricing` (`type`, `currency`, `relid`, `msetupfee`) VALUES(:type, :currency, :relid, :price)");
			$statement->execute( [
									':type' => $type,
									':currency' => $currencyId,
									':relid' => $id,
									':price' => $price,
								 ]);
			$pdo->commit();
			
			return true;
		} catch (\Exception $e) {
			echo "<div class='alert alert-danger alert-dismissable'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><strong><span class='title'>Mise à jour de tarifs :</span></strong> {$e->getMessage()}</div>";
			$pdo->rollback();
			return false;
		}
	}
	
	
    /**
     * Get Id of the price for a TLD.
     *
     * @param int $extId Id of the tld
     * @param int $type type of price : domainresgister, domaintransfer, domainrenewal etc
     *
     * @return int
     */
	private function getPriceId($extId, $type){
		$rows = Capsule::table('tblpricing')
			->where('relid',$extId)
			->where('type',$type)
			->get();
		if (count($rows) == 1 )  {
			$row = $rows[0];
			return $row->id;
		}
		
		return -1;
	}
	
    /**
     * Get Id of a TLD.
     *
     * @param int $ext TLD to find
     *
     * @return int
     */
	private function getExtId( $ext ){
		$rows = Capsule::table('tbldomainpricing')
			->where('extension', $ext)
			->get();
		if (count($rows) != 1 ) return false;

		return $rows[0]->id;
	}
	
    /**
     * Get Id of the currency.
     *
     * @param string $currencycode Code for the currency (EUR etc )
     *
     * @return int
     */
	private function getCurrencyId($currencycode){
		$rows = Capsule::table('tblcurrencies')
			->where('CODE', $currencycode)
			->get();
		if (count($rows) != 1 ) return false;

		return $rows[0]->id;
	}
	
	private function doImport( $ext, $register, $transfer, $renew){

		$extid = $this->getExtId($ext);
		if ($extid < 1 ) return false;
		
		$currencyId = $this->getCurrencyId("EUR");
		$rowid = $this->getPriceId($extid, "domainregister");

		if ($rowid > 0) {
			$this->updatePrice($rowid, $register);
		} else {
			$this->insertPrice($extid, $currencyId, "domainregister", $register);
		}
		
		$rowid = $this->getPriceId($extid, "domaintransfer");
		if ($rowid > 0) {
			$this->updatePrice($rowid, $transfer);
		} else {
			$this->insertPrice($extid, $currencyId, "domaintransfer", $transfer);
		}

		$rowid = $this->getPriceId($extid, "domainrenew");
		if ($rowid > 0) {
			$this->updatePrice($rowid, $renew);
		} else {
			$this->insertPrice($extid, $currencyId, "domainrenew", $renew);
		}

		return true;
	}
	public function import($vars){
		$modulelink = $vars['modulelink']; // eg. addonmodules.php?module=addonmodule
		$req      = $vars["request"];
		$marge    = $req['marge'];
		$arrondir = $req['arrondir'];
		$ext      = $req["ext"];
		$register = $req["register"];
		$transfer = $req["transfer"];
		$renew    = $req["renew"];
		$vars['arrondir'] = $arrondir;
		$vars['marge'] = $marge;

		if($this->doImport($ext, $register, $transfer, $renew)){
			$result = "<div class='alert alert-success alert-dismissable'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><strong><span class='title'>Mise à jour de tarifs :</span></strong> Votre liste de prix à correctement été mise à jour.</div>";
		} else {
			$result = "<div class='alert alert-danger alert-dismissable'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><strong><span class='title'>Mise à jour de tarifs :</span></strong> Erreur lors de l'enregistrement !</div>";
		}
		$vars['result'] = $result;

		$this->afficherPage($vars);
	}
	
	
	private function doAdd($ext){
		try {
			$pdo = Capsule::connection()->getPdo();
			$pdo->beginTransaction();
			$statement = $pdo->prepare( "insert into `tbldomainpricing` (`extension`, `dnsmanagement`, `emailforwarding`, `idprotection`, `eppcode`, `autoreg`) VALUES 
																		(:extension,  'on',  'on',  '',  'on',  :registrar )");
			$statement->execute( [
									':extension' => $ext,
									':registrar' => "francedns",
								 ]);
			$pdo->commit();
			
			return true;
		} catch (\Exception $e) {
			echo "<div class='alert alert-danger alert-dismissable'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><strong><span class='title'>Ajout TLD :</span></strong> {$e->getMessage()}</div>";
			$pdo->rollback();
			return false;
		}
	}	
	public function ajouter($vars){
		$modulelink = $vars['modulelink']; // eg. addonmodules.php?module=addonmodule
		$req      = $vars["request"];
		$marge    = $req['marge'];
		$arrondir = $req['arrondir'];
		$ext      = $req["ext"];
		$vars['arrondir'] = $arrondir;
		$vars['marge'] = $marge;
		
		if($this->doAdd($ext)){
			$result = "<div class='alert alert-success alert-dismissable'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><strong><span class='title'>Ajout TLD :</span></strong> l'extension <b>$ext</b> a bien été ajoutée.</div>";
		} else {
			$result = "<div class='alert alert-danger alert-dismissable'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><strong><span class='title'>Ajout TLD :</span></strong> Erreur lors de l'enregistrement !</div>";
		}
		$vars['result'] = $result;

		$this->afficherPage($vars);				
	}
	
	private function doRemove($ext){
		$extid = $this->getExtId($ext);
		if ($extid < 1 ) return false;
		try {
			$pdo = Capsule::connection()->getPdo();
			$pdo->beginTransaction();
			$statement = $pdo->prepare( "delete from `tblpricing` where `type` like 'domain%' and `relid`=:id");
			$statement->execute( [
								   ':id' => $extid,
								 ]);
			$pdo->commit();
			
			$pdo->beginTransaction();
			$statement = $pdo->prepare( "delete from `tbldomainpricing` where `id`=:id");
			$statement->execute( [
								   ':id' => $extid,
								 ]);
			$pdo->commit();
			return true;
		} catch (\Exception $e) {
			echo "<div class='alert alert-danger alert-dismissable'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><strong><span class='title'>Mise à jour de tarifs :</span></strong> {$e->getMessage()}</div>";
			$pdo->rollback();
			return false;
		}
	}
	public function remove($vars){
		$modulelink = $vars['modulelink']; // eg. addonmodules.php?module=addonmodule
		$req      = $vars["request"];
		$marge    = $req['marge'];
		$arrondir = $req['arrondir'];
		$ext      = $req["ext"];
		$vars['arrondir'] = $arrondir;
		$vars['marge'] = $marge;
		
		if($this->doRemove($ext)){
			$result = "<div class='alert alert-success alert-dismissable'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><strong><span class='title'>Suppressions TLD :</span></strong> l'extension <b>$ext</b> a bien été supprimée.</div>";
		} else {
			$result = "<div class='alert alert-danger alert-dismissable'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><strong><span class='title'>Ajout TLD :</span></strong> Erreur lors de l'enregistrement !</div>";
		}
		$vars['result'] = $result;

		$this->afficherPage($vars);				
	}

}
