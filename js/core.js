// JavaScript Document
jQuery.noConflict();

var $ = null;
jQuery(document).ready(function($$)
{
	$ = $$;
	$("#InstallAction").click(function()
	{
		requestserver("Installing","install");
		$(".skinStatus").css("color","Blue"); 
	});
	$("#UninstallAction").click(function()
	{
		requestserver("Deleting","uninstall");
		$(".skinStatus").css("color","Red");
	});
});


function requestserver(Status,option)
{
	try
	{
		$.ajax(
		{
			type: "POST",
			url: "controller.php",
			data: "option="+option,
			beforeSend: function()
			{
				$(".skinStatus > div").html(Status);
			},
			success: function(msg)
			{
				$(".skinStatus > div").html(msg);
				$(".skinStatus").scrollTop($(".skinStatus > div").height());
			}
		});
	}catch(exception){error(exception)};
}