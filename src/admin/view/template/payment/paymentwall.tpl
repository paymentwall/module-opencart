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
                        <td><span class="required">*</span> <?php echo $entry_key; ?></td>
                        <td>
                            <input type="text" name="paymentwall_key" value="<?php echo $paymentwall_key; ?>"/>
                            <?php if ($error_key) { ?>
                            <span class="error"><?php echo $error_key; ?></span>
                            <?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <td><span class="required">*</span> <?php echo $entry_secret; ?></td>
                        <td>
                            <input type="text" name="paymentwall_secret" value="<?php echo $paymentwall_secret; ?>"/>
                            <?php if ($error_secret) { ?>
                            <span class="error"><?php echo $error_secret; ?></span>
                            <?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <td><span class="required">*</span> <?php echo $entry_widget; ?></td>
                        <td>
                            <input type="text" name="paymentwall_widget" value="<?php echo $paymentwall_widget; ?>"/>
                            <?php if ($error_widget) { ?>
                            <span class="error"><?php echo $error_widget; ?></span>
                            <?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <td><?php echo $entry_complete_status;?></td>
                        <td>
                            <select name="complete_status">
                                <?php foreach($statuses as $key => $value) { ?>
                                <option value="<?php echo $key; ?>"
                                <?php if($key == $complete_status) echo ' selected="selected" ' ?> >
                                <?php echo $value; ?>
                                </option>
                                <?php }?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><?php echo $entry_cancel_status;?></td>
                        <td><select name="cancel_status">
                                <?php foreach($statuses as $key => $value) { ?>
                                <option value="<?php echo $key; ?>" <?php if($key == $cancel_status) echo ' selected="selected" ' ?> >
                                <?php echo $value; ?>
                                </option>
                                <?php }?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><?php echo $entry_test;?></td>
                        <td>
                            <?php if ($paymentwall_test) { ?>
                            <input type="radio" name="paymentwall_test" value="1" checked="checked" />
                            <?php echo $text_enabled; ?>
                            <input type="radio" name="paymentwall_test" value="0" />
                            <?php echo $text_disabled; ?>
                            <?php } else { ?>
                            <input type="radio" name="paymentwall_test" value="1" />
                            <?php echo $text_enabled; ?>
                            <input type="radio" name="paymentwall_test" value="0" checked="checked" />
                            <?php echo $text_disabled; ?>
                            <?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <td><?php echo $entry_active;?></td>
                        <td>
                            <select name="paymentwall_status">
                                <?php if ($paymentwall_status) { ?>
                                <option value="0"><?php echo $text_disabled; ?></option>
                                <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                                <?php } else { ?>
                                <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                                <option value="1"><?php echo $text_enabled; ?></option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
    </div>
</div>
<?php echo $footer; ?>