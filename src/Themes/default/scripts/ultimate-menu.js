/* Ultimate-menu JavaScript */
var	umel = document.createElement("a"), umdiv = document.getElementById("group_perms");
if (umdiv) {
	let	uml = umdiv.firstElementChild, a = document.createElement("a");
	umel.textContent = uml.textContent;
	umel.className = "toggle_down";
	umel.href = "#";
	umel.style.display = "";
	umel.addEventListener("click", function(event) {
		umdiv.classList.remove("hidden");
		this.style.display = "none";
		event.stopPropagation();
		event.preventDefault();
	});
	umdiv.classList.add("hidden");
	umdiv.parentNode.appendChild(umel);
	a.className = "toggle_up";
	a.textContent = uml.textContent;
	a.href = "#";
	a.style.display = "";
	a.addEventListener("click", function(event) {
		umdiv.classList.add("hidden");
		umel.style.display = "";
		event.stopPropagation();
		event.preventDefault();
	});
	uml.textContent = "";
	uml.appendChild(a);
	umdiv.lastElementChild.firstElementChild.addEventListener("click", function() {
		invertAll(this, this.form, "permissions[]");
	});
}
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
		class: "um_icons windowbg",
		id: "um_list"
	}), $um_nofile = $("select#um_icon_select option:first").text(),
		$um_selected = $("select#um_icon_select option:selected").text();
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
				console.log("Upload successful: " + JSON.stringify(response.file, null, 2));
				$("#um_file").val("");
				if (response.file) {
					$("#um_icon_img").attr("src", smf_default_theme_url + "/images/um_icons/" + $.trim(response.file));
					$(".ultimateMenu_drop>ul li").removeClass("um_icon_selected").addClass("um_icon");
					$("<li />", {
						"data-value": $.trim(response.file),
						text: $.trim(response.file),
						class: "um_icon_selected"
					}).appendTo($um_ul);
					$(".hideSelect").text(response.file);
				}
				else if (response.error) {
					console.log(response.error);
					alert(response.error);
				}
				$("#um_loader").css("display", "none");
			},
			error: function(xhr, status, error) {
				$("#um_file").val("");
				console.log("Upload failed: " + error);
				$("#um_loader").css("display", "none");
			}
		});
	});
	$("#um_icon").on("change", function() {
		if ($(this).val()) {
			$("#um_icon_img").attr("src", smf_default_theme_url + "/images/um_icons/" + $(this).val());
		}
	});
	$("img#um_icon_img").on("mouseover", function() {
		$(this).css("cursor", "zoom-in");
		$(this).stop().animate({
			width: "40px",
			height: "40px",
			top: "-25px",
			left: "-25px"
		}, "medium");
	}).on("mouseout", function() {
		$(this).css("cursor", "default");
		$(this).stop().animate({
			width: "16px",
			height: "16px",
			top: "0px",
			left: "0px"
		}, "medium");
	});
	$("#um_icon_select").find("option").each(function() {
		$("<li />", {
			"data-value": $.trim($(this).val()),
			text: $.trim($(this).text()),
		}).appendTo($um_ul);
	});
	$("#um_icon_select").remove();
	$um_ul.appendTo(".ultimateMenu_drop");
	$(".hideSelect").css("display", "inline-block");
	$(".hideSelect").text( (!$("input#um_icon").val() ? $.trim($um_selected) : $.trim($("input#um_icon").val())));
	$umInput.appendTo('#um_icon_list');
	$(".ultimateMenu_drop").on("click", function(e) {
      e.stopPropagation();
      $(".ultimateMenu_drop>ul").stop().slideToggle(1000);
      $(document).on("click", function() {
        $(".ultimateMenu_drop>ul").hide();
      });
    });
    $(".ultimateMenu_drop>ul li").on('click', function() {
		$(".ultimateMenu_drop>ul li").removeClass("um_icon_selected").addClass("um_icon");
		$(this).removeClass("um_icon").addClass("um_icon_selected");
		$(".hideSelect").text($.trim($(this).text()));
		$("input#um_icon").val($.trim($(this).data("value")));
		$("img#um_icon_img").attr("src", smf_default_theme_url + "/images/um_icons/" + $.trim($(this).data("value")));
    });
});
