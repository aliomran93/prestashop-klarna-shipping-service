{extends file="helpers/form/form.tpl"}
{block name="script"}
	var string_price = '{l s='Will be applied when the price is' js=1}';
	var string_weight = '{l s='Will be applied when the weight is' js=1}';
{/block}

{block name="field"}
	{if $input.name == 'zones'}
		<div class="ranges_not_follow warn" style="display:none">
			<label>{l s="Ranges are not correctly ordered:"}</label>
			<a href="#" onclick="checkRangeContinuity(true); return false;" class="btn btn-default">{l s="Reordering"}</a>
		</div>
		{include file='controllers/carrier_wizard/helpers/form/form_ranges.tpl'}

		<div class="new_range">
			<a href="#" onclick="add_new_range();return false;" class="btn btn-default" id="add_new_range">{l s='Add new range'}</a>
		</div>
	{/if}
	{if $input.name == 'logo'}
		<div class="col-lg-9">
			<input id="carrier_logo_input" class="hide" type="file" onchange="uploadCarrierLogo();" name="carrier_logo_input" />
			<input type="hidden" id="logo" name="logo" value="" />
			<div class="dummyfile input-group">
				<span class="input-group-addon"><i class="icon-file"></i></span>
				<input id="attachement_filename" type="text" name="filename" readonly="" />
				<span class="input-group-btn">
					<button id="attachement_fileselectbutton" type="button" name="submitAddAttachments" class="btn btn-default">
						<i class="icon-folder-open"></i> {l s='Choose a file'}
					</button>
				</span>
			</div>
			<p class="help-block">
					{l s='Format:'} JPG, GIF, PNG. {l s='Filesize:'} {$max_image_size|string_format:"%.2f"} {l s='MB max.'}
					{l s='Current size:'} <span id="carrier_logo_size">{l s='undefined'}</span>.
			</p>
		</div>
	{/if}
	{$smarty.block.parent}
{/block}
{block name="input_row"}
    {if $input.type == "kss_section"}
    <hr>
    <div class="well">
        <div class="panel-heading">
            Klarna shipping service section
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">
                Shipping type
            </label>
            <div class="col-lg-9">
                <select name="shipping_type" class=" fixed-width-xl" id="kss_shipping_type">
                    <option value="pickup-box" {if isset($shipping_type) && $shipping_type == "pickup-box"}selected{/if}>Box carrier</option>
                    <option value="pickup-point" {if isset($shipping_type) && $shipping_type == "pickup-point"}selected{/if}>Pickup point</option>
                    <option value="delivery-address" {if isset($shipping_type) && $shipping_type == "delivery-address"}selected{/if}>Delivery address</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">
                <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="e.g. budbee, dhl, postnord, etc">
                    Klarna shipping service name
                </span>
            </label>
            <div class="col-lg-9">
                <input type="text" name="shipping_name" id="kss_carrier_name" value="{if isset($shipping_name)}{$shipping_name}{/if}" class="">
                <p class="help-block">
                    <a target="top" href="https://developers.klarna.com/api/#klarna-shipping-service-callback-api__shipping_option__carrier">Read more here.</a>
                </p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">
                Carrier class
            </label>
            <div class="col-lg-9">
                <select name="shipping_class" class=" fixed-width-xl" id="kss_carrier_class">
                <option value="standard" {if isset($shipping_class) && $shipping_class == "standard"}selected{/if}>Standard</option>
                    <option value="express" {if isset($shipping_class) && $shipping_class == "express"}selected{/if}>Express</option>
                    <option value="economy" {if isset($shipping_class) && $shipping_class == "economy"}selected{/if}>Economy</option>
                    <option value="courier" {if isset($shipping_class) && $shipping_class == "courier"}selected{/if}>Courier</option>
                </select>
            </div>
        </div>
        <div id="displayFeatures">
            <table id="addedFeatures">
                <thead>
                    <th data-column-index="feature_class">class</th>
                    <th data-column-index="feature_type">type</th>
                    <th data-column-index="feature_url">url</th>
                    <th>remove</th>
                </thead>
                <tbody>
                {if !empty($shipping_features)}
                    {foreach from=$shipping_features item=feature}
                        <tr>
                            <td value="{$feature['feature_class']}">
                                {$feature['feature_class']}
                                <input type="hidden" name="kss_features[{$feature@iteration - 1}][feature_class]" value="{$feature['feature_class']}">
                            </td>
                            <td value="{$feature['feature_type']}">
                                {$feature['feature_type']}
                                <input type="hidden" name="kss_features[{$feature@iteration - 1}][feature_type]" value="{$feature['feature_type']}">
                            </td>
                            {if empty($feature['feature_url'])}
                                <td></td>
                            {else}
                                <td value="{$feature['feature_url']}">
                                    {$feature['feature_url']}
                                    <input type="hidden" name="kss_features[{$feature@iteration - 1}][feature_url]" value="{$feature['feature_url']}">
                                </td>
                            {/if}
                            <td><a class="btn removeFeatureInput"><i class="icon-trash"></i></a></td>
                        </tr>
                    {/foreach}
                {/if}
                </tbody>
            </table>
        </div>
        <div class="button-container">
            <input type="button" class="btn btn-default" id="addFeature" value="add klarna shipping service feature">
        </div>
    </div>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
