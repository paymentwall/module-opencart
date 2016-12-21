<?php echo $header; ?>
<div id="content">
    <div class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb) { ?>
        <?php echo $breadcrumb['separator']; ?>
        <a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
        <?php } ?>
    </div>
    <?php if ($error_warning) { ?>
    <div class="warning"><?php echo $error_warning; ?></div>
    <?php } ?>
    <div class="box">
        <div class="heading">
            <h1><img src="view/image/payment.png" alt=""/> <?php echo $heading_title; ?></h1>

            <div class="buttons">
                <a onclick="$('#form').submit();" class="button"><?php echo $button_save; ?></a>
                <a onclick="location = '<?php echo $cancel; ?>';" class="button"><?php echo $button_cancel; ?></a>
            </div>
        </div>
        <div class="content">
            <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
                <table class="form">
                    <tr>
                        <td colspan="2"><p><?php echo $text_brick_register ?></p></td>
                    </tr>
                    <tr>
                        <td>
                            <span class="required">*</span> <?php echo $entry_public_key; ?>
                        </td>
                        <td>
                            <input type="text" name="brick_public_key" value="<?php echo $brick_public_key; ?>" size="40"/>
                            <?php if (!$error_key) { ?>
                            <span class="error"><?php echo $error_key; ?></span>
                            <?php } ?></td>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="required">*</span> <?php echo $entry_private_key; ?>
                        </td>
                        <td>
                            <input type="text" name="brick_private_key" value="<?php echo $brick_private_key; ?>" size="40"/>
                            <?php if (!$error_private) { ?>
                            <span class="error"><?php echo $error_private; ?></span>
                            <?php } ?></td>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <?php echo $entry_public_test_key; ?>
                        </td>
                        <td>
                            <input type="text" name="brick_public_test_key" value="<?php echo $brick_public_test_key; ?>" size="40"/>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?php echo $entry_private_test_key; ?>
                        </td>
                        <td>
                            <input type="text" name="brick_private_test_key" value="<?php echo $brick_private_test_key; ?>" size="40"/>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <span class="required">*</span> <?php echo $entry_paymentwall_secret_key; ?>
                        </td>
                        <td>
                            <input type="text" name="brick_secret_key" value="<?php echo $brick_secret_key; ?>" size="40"/>
                            <?php if (!$error_secret) { ?>
                            <span class="error"><?php echo $error_secret; ?></span>
                            <?php } ?></td>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <?php echo $entry_pingback_url; ?>
                        </td>
                        <td>
                            <?php echo $text_pingback_url; ?>
                        </td>
                    </tr>

                    <tr>
                        <td><?php echo $entry_complete_status;?></td>
                        <td>
                            <select name="brick_complete_status">
                                <?php foreach($statuses as $key => $value) { ?>
                                <option value="<?php echo $key; ?>"
                                <?php if($key == $brick_complete_status) echo ' selected="selected" ' ?> >
                                <?php echo $value; ?>
                                </option>
                                <?php }?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><?php echo $entry_under_review_status;?></td>
                        <td><select name="brick_under_review_status">
                                <?php foreach($statuses as $key => $value) { ?>
                                <option value="<?php echo $key; ?>" <?php if($key == $brick_under_review_status) echo ' selected="selected" ' ?> >
                                <?php echo $value; ?>
                                </option>
                                <?php }?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><?php echo $entry_cancel_status;?></td>
                        <td><select name="brick_cancel_status">
                                <?php foreach($statuses as $key => $value) { ?>
                                <option value="<?php echo $key; ?>" <?php if($key == $brick_cancel_status) echo ' selected="selected" ' ?> >
                                <?php echo $value; ?>
                                </option>
                                <?php }?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><?php echo $entry_test_mode;?></td>
                        <td>
                            <?php if ($brick_test_mode) { ?>
                            <input type="radio" name="brick_test_mode" value="1" checked="checked" />
                            <?php echo $text_enabled; ?>
                            <input type="radio" name="brick_test_mode" value="0" />
                            <?php echo $text_disabled; ?>
                            <?php } else { ?>
                            <input type="radio" name="brick_test_mode" value="1" />
                            <?php echo $text_enabled; ?>
                            <input type="radio" name="brick_test_mode" value="0" checked="checked" />
                            <?php echo $text_disabled; ?>
                            <?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <td><?php echo $entry_active;?></td>
                        <td>
                            <select name="brick_status">
                                <?php if ($brick_status) { ?>
                                <option value="0"><?php echo $text_disabled; ?></option>
                                <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                                <?php } else { ?>
                                <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                                <option value="1"><?php echo $text_enabled; ?></option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><?php echo $entry_sort_order; ?></td>
                        <td><input type="text" name="brick_sort_order" value="<?php echo $brick_sort_order; ?>" size="3" /></td>
                    </tr>
                </table>
            </form>
        </div>
    </div>
</div>
<?php echo $footer; ?>