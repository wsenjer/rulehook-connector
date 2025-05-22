<h3><?php _e('RuleHook Connector Settings', 'simple-table-rates');?></h3>
<?php if ($this->debug_mode == 'yes'): ?>
	<div class="updated woocommerce-message">
		<p><?php _e('RuleHook Connector debug mode is activated, only administrators can use it.', 'simple-table-rates');?></p>
	</div>
<?php endif;?>
<div id="poststuff" class="str_settings">
	<div id="post-body" class="metabox-holder columns-2">
		<div id="post-body-content">
			<table class="form-table">
				<?php echo $this->get_admin_options_html(); ?>
			</table><!--/.form-table-->
		</div>
		<div id="postbox-container-1" class="postbox-container wpruby-widgets">
			<div id="side-sortables" class="meta-box-sortables ui-sortable">
				<div class="postbox ">
					<h3 class="hndle"><span class="dashicons dashicons-editor-help"></span>&nbsp;&nbsp;<?php _e('Plugin Support', 'simple-table-rates'); ?></h3>
					<hr>
					<div class="inside">
						<div class="support-widget">
							<p style="text-align: center">
								<a target="_blank" href="https://wpruby.com">
									<img alt="WPRuby" style="width:60%;" src="https://wpruby.com/wp-content/uploads/2016/03/wpruby_logo_with_ruby_color-300x88.png">
								</a>
							</p>
							<p>
								<?php _e('Top Notch WordPress Plugins!', 'simple-table-rates'); ?>
							</p>
							<ul>
								<li>» <a href="<?php echo admin_url('admin.php?page=woocommerce-rulehook-connector-activation'); ?>" target="_blank"><?php _e('Add/Update Your license', 'simple-table-rates'); ?></a></li>
								<li>» <a href="https://wpruby.com/submit-ticket/" target="_blank"><?php _e('Support Request', 'simple-table-rates'); ?></a></li>
								<li>» <a href="https://wpruby.com/knowledgebase/how-to-renew-your-license/" target="_blank"><?php _e('How to renew your license', 'simple-table-rates'); ?></a></li>
								<li>» <a href="https://wpruby.com/knowledgebase/how-to-upgrade-you-license/" target="_blank"><?php _e('How to upgrade my license', 'simple-table-rates'); ?></a></li>
								<li>» <a href="https://wpruby.com/knowledgebase/change-plugins-license-domain/" target="_blank"><?php _e('How to change your license’s domain', 'simple-table-rates'); ?></a></li>
								<li>» <a href="https://wpruby.com/knowledgebase_category/woocommerce-rulehook-connector/" target="_blank"><?php _e('Documentation', 'simple-table-rates'); ?></a></li>
								<li>» <a href="https://wpruby.com/plugins/" target="_blank"><?php _e('Our Plugins Shop', 'simple-table-rates'); ?></a></li>
							</ul>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="clear"></div>
<style type="text/css">
    #postbox-container-1 .note{
        background: #ffba00;
        color:#ffffe0;
    }
    .wpruby-widgets .hndle {
        padding: 10px 0 5px 10px !important;
    }
    .support-widget p{
        text-align: center;
    }
    .str_settings .form-table th {
        width:150px;
    }
</style>
