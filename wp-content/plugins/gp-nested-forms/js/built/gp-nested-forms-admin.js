(()=>{var e,t;window.GPNFAdmin=window.GPNFAdmin||{},t=jQuery,e=function(){var e=this;e.init=function(){e.$formSelect=t("#gpnf-form"),e.$fieldSelect=t("#gpnf-fields"),e.$formSettings=t("#gpnf-form-settings"),e.$entryLabelSingular=t("#gpnf-entry-label-singular"),e.$entryLabelPlural=t("#gpnf-entry-label-plural"),e.$entryLimitMin=t("#gpnf-entry-limit-min"),e.$entryLimitMax=t("#gpnf-entry-limit-max"),e.$feedProcessing=t("#gpnf-feed-processing"),e.$modalHeaderColor=t("#gpnf-modal-header-color"),e.$editChildForm=t("#gpnf-edit-child-form"),t(document).bind("gform_load_field_settings",(function(n,i,o){if("form"==i.type){var l=i.gpnfForm;e.$entryLabelSingular.val(i.gpnfEntryLabelSingular),e.$entryLabelPlural.val(i.gpnfEntryLabelPlural),e.$entryLimitMin.val(i.gpnfEntryLimitMin),e.$entryLimitMax.val(i.gpnfEntryLimitMax),e.$feedProcessing.val(e.getFeedProcessingSetting(i)),e.$modalHeaderColor.val(i.gpnfModalHeaderColor),t("#chip_gpnf-modal-header-color").css("background-color",i.gpnfModalHeaderColor),e.$formSelect.val(l),e.setEditChildFormLink(l);var r=i.gpnfFields?i.gpnfFields:[];e.toggleNestedFormFields(r)}})),t("#chooser_gpnf-modal-header-color, #chip_gpnf-modal-header-color").click((function(e){iColorShow(e.pageX-245,e.pageY-57,"gpnf-modal-header-color","gpnfSetModalHeaderColor")})),t().add(e.$entryLabelSingular).add(e.$entryLabelPlural).on("change",(function(){RefreshSelectedFieldPreview()}))},e.toggleNestedFormFields=function(t){e.$fieldSelect.attr("disabled",!0),e.$formSelect.val()?(e.$formSettings.show(),e.getFormFields(e.$formSelect.val(),t||[])):e.$formSettings.hide()},e.setEditChildFormLink=function(t){e.$editChildForm.attr("href","?page=gf_edit_forms&id="+t)},e.sortAsmSelectDropdown=function(t){var n=e.$fieldSelect.siblings(".asmSelect"),i=t.map((function(e){return e.id.toString()}));n.find('option:not([value=""])').sort((function(e,t){return(e=i.indexOf(e.value.toString()))-i.indexOf(t.value.toString())})).appendTo(n)},e.setFieldsSelect=function(n,i){var o=[];if(!n.length&&i.length)for(var l=0;l<i.length;l++)o.push('<option value="'+i[l]+'" selected="selected">'+i[l]+"</option>");else{var r=t.extend([],n).sort((function(e,t){return(e=i.indexOf(e.id.toString()))-i.indexOf(t.id.toString())}));for(l=0;l<r.length;l++){var a=r[l],d=-1!=t.inArray(String(a.id),i)?'selected="selected"':"";-1===t.inArray(a.type,["page","html","section","captcha"])&&o.push('<option value="'+a.id+'"'+d+">"+GetLabel(a)+"</option>")}}if(e.$fieldSelect,e.$fieldSelect.html(o.join("")).val(i).change(),e.$fieldSelect.data("asmApplied"))e.sortAsmSelectDropdown(n);else{var f=function(e){SetFieldProperty("gpnfFields",e),RefreshSelectedFieldPreview()};e.$fieldSelect.asmSelect({addItemTarget:"bottom",highlight:!0,sortable:!0}).data("asmApplied",!0);var s=e.$fieldSelect.siblings(".asmListSortable");s.sortable("option","axis",""),s.sortable("option","scroll",!1),s.on("sortstart",(function(){var e=t(this).sortable("instance");e.offset.parent=e._getParentOffset()})),s.on("sortupdate",(function(){setTimeout((function(){f(e.$fieldSelect.val())}),5)})),e.$fieldSelect.change((function(){f(t(this).val())})),e.sortAsmSelectDropdown(n)}},e.getFormFields=function(n,i){t.post(ajaxurl,{action:"gpnf_get_form_fields",nonce:GPNFAdminData.nonces.getFormFields,form_id:n},(function(t){e.$formSettings.find("select").attr("disabled",!1),"object"==typeof t?e.setFieldsSelect(t,i):alert(GPNFAdminData.strings.getFormFieldsError)}))},e.getFeedProcessingSetting=function(e){return e.gpnfFeedProcessing?e.gpnfFeedProcessing:"parent"},e.init()},window.gpnfSetModalHeaderColor=function(e){SetFieldProperty("gpnfModalHeaderColor",e)},t(document).ready((function(){window.gpGlobals||(window.gpGlobals={}),window.gpGlobals.GPNFAdmin=new e}))})();
//# sourceMappingURL=gp-nested-forms-admin.js.map