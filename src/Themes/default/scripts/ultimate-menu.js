/* Ultimate-menu JavaScript */
var	umel = document.createElement("a"), umdiv = document.getElementById("group_perms");
if (umdiv) {
	let	uml = umdiv.firstElementChild, a = document.createElement("a");
	umel.textContent = uml.textContent;
	umel.className = "toggle_down";
	umel.href = "#";
	umel.style.display = "";
	umel.addEventListener("click", function(event)
	{
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
	a.addEventListener("click", function(event)
	{
		umdiv.classList.add("hidden");
		umel.style.display = "";
		event.stopPropagation();
		event.preventDefault();
	});
	uml.textContent = "";
	uml.appendChild(a);
	umdiv.lastElementChild.firstElementChild.addEventListener("click", function()
	{
		invertAll(this, this.form, "permissions[]");
	});
}
$(document).ready(function() {
	$("#um_file").on("change", function(e) {
		if (!um_secureCode) {
			return false;
		}
		var fileData = $("#um_file").prop("files")[0];
		var fileName = e.target.files[0].name;
		var formData = new FormData();
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
				console.log("Upload successful: " + response);
				if (response.file) {
					$("#um_icon_img").attr("src", smf_default_theme_url + "/images/um_icons/" + response.file);
					$("#um_icon").append($("<option>", {
						value: response.file,
						text: response.file,
						selected: true
					}));
				}
				else if (response.error) {
					console.log(response.error);
					alert(response.error);
				}
				$("#um_loader").css("display", "none");
			},
			error: function(xhr, status, error) {
				console.log("Upload failed: " + error);
				$("#um_loader").css("display", "none");
			},
			xhr: function() {
				var myXhr = $.ajaxSettings.xhr();
				if (myXhr.upload) {
					myXhr.upload.addEventListener("progress", function(e) {
						if (e.lengthComputable) {
							var percentComplete = parseInt((e.loaded / e.total) * 100);
						}
					}, false);
				}
				return myXhr;
			}
		});
	});
	$("#um_icon").on("change", function() {
		if ($(this).val()) {
			$("#um_icon_img").attr("src", smf_default_theme_url + "/images/um_icons/" + $(this).val());
			$(this).prop("selected", true);
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
	}).on( "mouseout", function() {
		$(this).css("cursor", "default");
		$(this).stop().animate({
			width: "16px",
			height: "16px",
			top: "0px",
			left: "0px"
		}, "medium");
	});
});
