<b><?php echo 'Redirect... Please Wait...'?></b>
<?php 
    $pathurl = ((!empty($_SERVER["HTTPS"]))? "https://":"http://"). $_SERVER['HTTP_HOST']. $_SERVER['PHP_SELF'];
    $pathurl = explode("cenpossimplewebpay.php", $pathurl);
    $pathurl = $pathurl[0];
    $pathurl = $pathurl."index.php/simplewebpay/processing/success/transaction_id/".$_REQUEST['invoicenumber'];
?>
<form name="simplewebpay" id="simplewebpay_place_form" action="<?php echo $pathurl;?>" method="POST">
<?php if (is_array($_REQUEST)): ?>
    <?php foreach ($_REQUEST as $name => $value): ?>
        <input type="hidden" name="<?php echo $name; ?>" value="<?php echo $value; ?>"/>
    <?php endforeach; ?>
<?php endif; ?>
</form>
<script type="text/javascript">
//<![CDATA[
    var paymentform = document.getElementById('simplewebpay_place_form');
    window.onload = paymentform.submit();
//]]>
</script>