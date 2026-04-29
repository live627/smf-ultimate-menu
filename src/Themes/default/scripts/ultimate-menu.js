/* Ultimate-menu JavaScript */
$(document).ready(function() {
	var $umInput = $("<input>", {
		id: "um_icon",
		type: "hidden",
		name: "icon",
		value: $('#um_icon').find(":selected").val(),
	}), $umJqInput = $("<input>", {
		id: "um_jq",
		type: "hidden",
		name: "um_jq",
		value: "1",
	}), $um_ul = $("<ul />", {
		class: "um_icons",
		id: "um_list"
	}), $um_nofile = $("select#um_icon_select option:first").text() ?? "",
		$um_selected = $("select#um_icon_select option:selected").text() ?? "";
	if ($("#group_perms")) {
		var $umel = $("<a />", {
			class: "toggle_down",
			href: "#",
			text: $("#group_perms").children(":first").text(),
			css: {"display":"inline"}
		}), $umlink = $("<a />", {
			class: "toggle_up",
			href: "#",
			text: $("#group_perms").children(":first").text(),
			css: {"display":"inline"}
		});
		$umel.on("click", function() {
			$("#group_perms").removeClass("hidden")
			$(this).css("display", "none");
			return false;
		});
		$umlink.on("click", function() {
			$("#group_perms").addClass("hidden");
			$umel.css("display", "inline");
			return false;
		});
		$("#group_perms").addClass("hidden").parent().append($umel);
		$("#group_perms").children(":first").text("").append($umlink);
		$("#group_perms").find("input:not([name])").on("click", function() {
			$('input[name="permissions\\[\\]"]').prop("checked", $(this).prop("checked"));
		});
	}
	$("#advum_icons").css("display","flex").addClass("advum_icons");
	$("#um_file").on("change", function(e) {
		if (!um_secureCode) {
			return false;
		}
		var fileData = $("#um_file").prop("files")[0], fileName = e.target.files[0].name, formData = new FormData();
		formData.append("attachment", fileData);
		formData.append(smf_session_var, smf_session_id);
		formData.append("um_checkcode", um_secureCode);
		$("#um_loader").css("display", "inline-flex");
		$.ajax({
			url: smf_scripturl + "/?action=admin;area=umen;sa=uploadicon",
			dataType: "json",
			cache: false,
			contentType: false,
			processData: false,
			data: formData,
			type: "POST",
			success: function(response) {
				$("#um_file").val("");
				if (response.file) {
					console.log("Upload successful: " + JSON.stringify(response.file, null, 2));
					$("span.um_icon_container").css("background-image", "url('" + smf_default_theme_url + "/images/um_icons/" + $.trim(response.file) + "')");
					$(".ultimateMenu_drop>ul li").removeClass("um_icon_selected").addClass("um_icon");
					$("<li />", {
						"data-value": $.trim(response.file),
						text: $.trim(response.file),
						class: "um_icon_selected"
					}).appendTo($um_ul);
					$(".um_hideSelect").text(response.file);
					$("input#um_icon").val($.trim(response.file));
					$("#um_sprite_inactive").trigger("click");
				}
				else if (response.error) {
					console.log(response.error);
					alert(response.error);
				}
				$("#um_loader").css("display", "none");
			},
			error: function(xhr, status, error) {
				$("#um_file").val("");
				console.log("Upload failed: " + JSON.stringify(error));
				$("#um_loader").css("display", "none");
			}
		});
	});
	$("#um_icon").on("change", function() {
		if ($(this).val()) {
			$("span.um_icon_container").css("background-image", "url('" + smf_default_theme_url + "/images/um_icons/" + $(this).val() + "')");
		}
	});
	$("#um_icon_select").find("option").each(function() {
		$("<li />", {
			"data-value": $.trim($(this).val()) ?? "",
			text: $.trim($(this).text()) || "",
		}).appendTo($um_ul);
	});
	$("#um_icon_select").remove();
	$um_ul.appendTo(".ultimateMenu_drop");
	$(".um_hideSelect").css("display", "inline-block");
	$(".um_hideSelect").text($.trim($("input#um_icon").val()) || $.trim($um_selected));
	$umInput.appendTo('#um_icon_list');
	$("#um_sprite_inactive").on('click', function(e) {
		e.stopPropagation();
	});
	$(".ultimateMenu_drop").on("click", function(e) {
		e.stopPropagation();
		$("#um_sprite_inactive").trigger("click");
		$(".ultimateMenu_drop>ul").stop().slideToggle(1000);
		$(document).on("click", function() {
        $(".ultimateMenu_drop>ul").hide();
      });
    });
    $(".ultimateMenu_drop>ul li").on('click', function() {
		$(".ultimateMenu_drop>ul li").removeClass("um_icon_selected").addClass("um_icon");
		$(this).removeClass("um_icon").addClass("um_icon_selected");
		$(".um_hideSelect").text($.trim($(this).text()) || "");
		$("input#um_icon").val($.trim($(this).data("value")) || "______");
		$("span.um_icon_container").css("background-image", "url('" + ($.trim($(this).data("value")) ? smf_default_theme_url + "/images/um_icons/" + $.trim($(this).data("value")) : "data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=") + "')");
    });
	$("input#um_icon").val($.trim($("span#um_hideSelect").text()) || "______");
	$('input[name="sprite"]').on("change", function(value) {
		if (parseInt($(this).val()) == 1) {
			$("span.um_icon_container").hide().css("visibility", "hidden");
			$("span.um_icon_pseudo").show().css("visibility", "visible");
		} else {
			$("span.um_icon_pseudo").hide().css("visibility", "hidden");
			$("span.um_icon_container").show().css("visibility", "visible");
		}
	});
});
