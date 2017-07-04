<style>
	.col-x40{
		width: 40px;
		float: left;
	}
	h2{
		border-bottom: 1px solid #9E9E9E;
	}
</style>
<h2 class="text-capitalize">{$vars['_lang']['configuration']} </h2>

<!-- <blockquote class="small">* FranceDNS TTC | Votre tarif | Nouveau prix | Marge réelle par rapport au coût</blockquote> -->
<form class="form-horizontal">
	<div class="form-group">
		<label class="control-label col-sm-2" for="marge">{$vars['_lang']['marge']} :</label>
		<div class="col-sm-1">
			<input type="number" id="marge" value="{$marge}" class="form-control"/>
		</div><label class="control-label">%</label>
	</div>
	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-2">
			<!--<label class="control-label col-sm-2" for="arrondi">{$vars['_lang']['roundto099']} :</label>-->
			<div class="checkbox">
				<label><input id="arrondi" type="checkbox" {if $arrondir}checked{/if}>{$vars['_lang']['roundto099']}</label>
			</div>
		</div>
	</div>
</form>

<hr>

<form class="btn-group pull-right" id="ajouter" method='post' action="{$modulelink}" style="margin-top: -7px;">
	<select name="ext" class="btn btn-primary btn-xs">
		{foreach $exts_fdns as $value }
		<option value="{$value}">{$value}</option>
		{/foreach}
	</select>
	<input type="hidden" name="action" value="ajouter">
	<input type="hidden" name="marge" value="{$marge}">
	<input type="hidden" name="arrondir" value="{$arrondir}">
	<a href="#" class="btn btn-success btn-xs" onclick="$(this).closest('form').submit()" style="height:23px;"><i class="fa fa-plus-circle"></i></a>
</form>	

<div id="dialog">{$result}</div>

<h2 class="text-capitalize">{$vars['_lang']['liste']} </h2>

<table class="table table-striped" id="tableimport">
	<thead>
		<tr>
			<th>{$vars['_lang']['Extension']}</th>
			<th>
				{$vars['_lang']['Enregistrement']}<br/>
				<span class="small col-x40">{$vars['_lang']['FDNS']}</span>
				<span class="small col-x40">{$vars['_lang']['Actuel']}</span>
				<span class="small col-x40">{$vars['_lang']['Futur']}</span>
				<span class="small col-x40">{$vars['_lang']['marge']}</span>
			</th>
			<th>{$vars['_lang']['Transfert']}<br/>
				<span class="small col-x40">{$vars['_lang']['FDNS']}</span>
				<span class="small col-x40">{$vars['_lang']['Actuel']}</span>
				<span class="small col-x40">{$vars['_lang']['Futur']}</span>
				<span class="small col-x40">{$vars['_lang']['marge']}</span>
			</th>
			<th>{$vars['_lang']['Renouvellement']}<br/>
				<span class="small col-x40">{$vars['_lang']['FDNS']}</span>
				<span class="small col-x40">{$vars['_lang']['Actuel']}</span>
				<span class="small col-x40">{$vars['_lang']['Futur']}</span>
				<span class="small col-x40">{$vars['_lang']['marge']}</span>
			</th>
			<th><span class="pull-right">{$vars['_lang']['Action']}</span></th>
		</tr>
	</thead>
	{foreach $exts as $value }
		{$hasElem=isset($localprices[$value])}
	<tr {if $hasElem}{else}class="danger"{/if}>
		<td><strong>{$value|upper}</strong></td>
		<td>
			{$localprice=$localprices[$value]["register"]}
			{$francednsprice=$francednsprices[$value]["register"]}
			
			<span class="col-x40" id="francednsprice" price_local='{$localprice}' price_fdns="{$francednsprice}">{$francednsprice|string_format:"%.2f"}</span>
			<span class="col-x40" id="localprice">{if $hasElem}{$localprice|string_format:"%.2f"}{else}&nbsp;{/if}</abbr></span>
			<span class="col-x40" id="newprice" price_local='{$localprice}' price_fdns="{$francednsprice}"></span>
			<span class="col-x40" id="prct" price_local='{$localprice}' price_fdns="{$francednsprice}"></span>
		</td>
		<td>
			{$localprice=$localprices[$value]["transfer"]}
			{$francednsprice=$francednsprices[$value]["transfer"]}
			
			<span class="col-x40" id="francednsprice" price_local='{$localprice}' price_fdns="{$francednsprice}">{$francednsprice|string_format:"%.2f"}</span> 
			<span class="col-x40" id="localprice">{if $hasElem}{$localprice|string_format:"%.2f"}{else}&nbsp;{/if}</span>
			<span class="col-x40" id="newprice" price_local='{$localprice}' price_fdns="{$francednsprice}"></span>
			<span class="col-x40" id="prct" price_local='{$localprice}' price_fdns="{$francednsprice}"></span>
		</td>
		<td>
			{$localprice=$localprices[$value]["renew"]}
			{$francednsprice=$francednsprices[$value]["renew"]}
			
			<span class="col-x40" id="francednsprice" price_local='{$localprice}' price_fdns="{$francednsprice}">{$francednsprice|string_format:"%.2f"}</span> 
			<span class="col-x40" id="localprice">{if $hasElem}{$localprice|string_format:"%.2f"}{else}&nbsp;{/if}</span>
			<span class="col-x40" id="newprice" price_local='{$localprice}' price_fdns="{$francednsprice}"></span>
			<span class="col-x40" id="prct" price_local='{$localprice}' price_fdns="{$francednsprice}"></span>
		</td>
		<td>
			{$marge=1.0}
			{$priceRegister=$francednsprices[$value]["register"]*$marge}
			{$priceTransfer=$francednsprices[$value]["transfer"]*$marge}
			{$priceRenew=$francednsprices[$value]["renew"]*$marge}
			<form name="remove{$value}" id="remove" method='post' action="{$modulelink}">
				<input type="hidden" name="action" value="remove">
				<input type="hidden" name="ext" value="{$value}">
				<input type="hidden" name="marge" value="{$marge}">
				<input type="hidden" name="arrondir" value="{$arrondir}">				
			</form>			
			<form name="importer{$value}" id="importer" method='post' action="{$modulelink}">
				<input type="hidden" name="action" value="import">
				<input type="hidden" name="register" value="{$priceRegister}" original="{$priceRegister}" isprice="true">
				<input type="hidden" name="transfer" value="{$priceTransfer}" original="{$priceTransfer}" isprice="true">
				<input type="hidden" name="renew" value="{$priceRenew}" original="{$priceRenew}" isprice="true">
				<input type="hidden" name="ext" value="{$value}">
				<input type="hidden" name="marge" value="{$marge}">
				<input type="hidden" name="arrondir" value="{$arrondir}">				
			</form>
			<div class="btn-group pull-right">
				<button type="button" class="btn btn-primary btn-xs" onclick="submitForm('importer{$value}')"><i class="fa fa-check"></i></a>
				<button type="button" class="btn btn-danger btn-xs" onclick="submitForm('remove{$value}')"><i class="fa fa-minus-circle"></i></a>
			</div>
		</td>
	</tr>
	{/foreach}
</table>

<script>
	_onEventChange() ;
	$("#marge,#arrondi").bind('keyup input change click', _onEventChange);
	
	function submitForm(formName){
		$('form[name="' + formName + '"]').submit();
	}
	
	function _onEventChange(){
		if ((! $("#marge").data("previousValue") || 
			   $("#marge").data("previousValue") != $("#marge").val()
		   ) || ( 
			! $("#arrondi").data("previousValue") || 
			  $("#arrondi").data("previousValue") != $("#arrondi").prop("checked")
		   
		   ))
	   {
			$("#marge").data("previousValue", $("#marge").val());
			$("#arrondi").data("previousValue", $("#arrondi").prop("checked"));
			changePrice($("#marge").val(), $("#arrondi").prop("checked"));
	   }
	}	
	
	function getArrondiMinusaCent(Value){
		return Math.trunc(Value + 1) * 1.0 - 0.01;
	}
	
	function getPrice( fdnsPrice, marge, arrondir ){
		var tmp = fdnsPrice.toFixed(2) * marge;
		if (arrondir){
			tmp = getArrondiMinusaCent(tmp);
		}
		
		return tmp.toFixed(2);
	}
	
	function changePrice(newMarge, arrondir){
		var marge = newMarge;
		newMarge = newMarge / 100 + 1;
		$("input[isprice='true']").each(function(){
				var price    = $(this).attr("original") * 1.0;
				var priceNew = getPrice(price, newMarge, arrondir);
				$(this).attr("value", priceNew);
			}
		);
		$("input[name='marge']").each(function(){
				$(this).attr("value", marge);
			}
		);
		$("input[name='arrondir']").each(function(){
				$(this).attr("value", arrondir);
			}
		);
		
		$("span[id='francednsprice']").each(function(){				
				var price = $(this).attr("price_local") * 1.0;
				var pricefdns = $(this).attr("price_fdns") * 1.0;
				var htmlValue = pricefdns.toFixed(2);
				
				priceTTC = getPrice(pricefdns, newMarge, arrondir) * 1.0;
				pricefdns = pricefdns.toFixed(2) * 1.0;
				if (pricefdns > priceTTC ){
					htmlValue = '<b class="ui-state-error">' + htmlValue + '</b>';
				}
				
				$(this).html(htmlValue);
			}			
		);
		
		$("span[id='newprice']").each(function(){				
				var price = $(this).attr("price_local") * 1.0;
				var pricefdns = $(this).attr("price_fdns") * 1.0;
				var priceNew = getPrice(pricefdns, newMarge, arrondir);
			
				price     = price.toFixed(2) * 1.0;
				pricefdns = pricefdns.toFixed(2) * 1.0;
				priceNew  = priceNew * 1.0;
							
				var htmlValue = priceNew.toFixed(2);
				if (priceNew < price){
					htmlValue = '<b class="ui-state-error">' + htmlValue + '</b>';
				} else
				if (priceNew > price){
					htmlValue = '<b class="ui-state-success">' + htmlValue + '</b>';
				} else {
					htmlValue = '<b class="ui-state-highlight">' + htmlValue + '</b>';
				}
				$(this).html(htmlValue);
			}
		);
		
		$("span[id='prct']").each(function(){				
				var price = $(this).attr("price_local") * 1.0;
				var pricefdns = $(this).attr("price_fdns") * 1.0;
				var priceNew = getPrice(pricefdns, newMarge, arrondir);
			
				price     = price.toFixed(2) * 1.0;
				pricefdns = pricefdns.toFixed(2) * 1.0;
				priceNew  = priceNew * 1.0;
							
				var htmlValue = "";
				//if (arrondir){
					var prct = (1-pricefdns/priceNew)*100;
					if (prct > 0 ) htmlValue = htmlValue + '&nbsp;<span class="ui-state-success">+' + prct.toFixed(2) + "%</span>"; else
					if (prct < 0 ) htmlValue = htmlValue + '&nbsp;<span class="ui-state-error">' + prct.toFixed(2) + "%</span>"; else
					htmlValue = htmlValue + '&nbsp;<span class="ui-state-highlight">' + prct.toFixed(2) + "%</span>";
				//}
				$(this).html(htmlValue);
			}
		);
	}
</script>

