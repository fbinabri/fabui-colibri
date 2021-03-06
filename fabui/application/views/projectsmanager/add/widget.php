<?php
/**
 * 
 * @author Krios Mane
 * @version 0.1
 * @license https://opensource.org/licenses/GPL-3.0
 * 
 */
 
?>
<div class="row">
	<div class="col-sm-12">
		<form id="object-form" method="post" class="smart-form" action="<?php echo site_url('projectsmanager/saveObject'); ?>">
			<fieldset>
				<div class="row">
					<section class="col col-6">
						<label class="label"><?php echo _("Name") ?></label>
						<label class="input">
							<input type="text" name="name" id="name">
						</label>
					</section>
					<section class="col col-6">
						<label class="label"><?php echo _("Public") ?></label>
						<div class="inline-group">
							<label class="radio">
								<input type="radio" checked="checked" name="public" value="1"><i></i> <?php echo _("Yes");?>
							</label>
							<label class="radio">
								<input type="radio" name="public" value="0"><i></i> <?php echo _("No");?>
							</label>
						</div>
					</section>
				</div>
				<section>
					<label class="label"><?php echo _("Description") ?></label>
					<label class="textarea textarea-resizable">
						<textarea name="description" rows="5" class="custom-scroll"></textarea> 
					</label>
				</section>
			</fieldset>
			<input type="hidden" name="files" id="files">
			<input type="hidden" name="usb_files" id="usb_files">
		</form>
	</div>
</div>
<!-- uploads row -->
<div class="row">
	<div class="col-sm-12">
		<ul id="myTab1" class="nav nav-tabs">
			<li class="active">
				<a href="#dropzone-tab" data-toggle="tab"><?php echo _("Dropzone") ?></a>
			</li>
			<li>
				<a href="#usb-tab" data-toggle="tab"><?php echo _("Usb disk") ?></a>
			</li>
		</ul>
		<div id="myTabContent1" class="tab-content padding-10">
			<div class="tab-pane fade in active" id="dropzone-tab">
				<div id="newObjectDropzone" class="dropzone"></div>
			</div>
			<div class="tab-pane fade in" id="usb-tab">
				<!-- tree is populated in js -->
				<div class="text-center">
							<h1><span style="font-size: 50px;" class="icon-fab-usb"></span></h1>
							<h1><?php echo _("Please insert USB disk") ?></h1>
							<a id="check-usb" class="btn btn-default" href="javascript:void(0);"><?php echo _("Reload") ?></a>
				</div>
			</div>
		</div>
	</div>
</div>
<!-- PROGRESS MODAL -->
<div class="modal fade" id="progressModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="myModalLabel"><i class="fa fa-upload"></i> <?php echo _("Upload progress") ?></h4>
			</div>
			<div class="modal-body custom-scroll " id="progressModalBody">
			</div>	
		</div>
	</div>
</div>
