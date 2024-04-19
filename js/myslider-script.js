jQuery(document).ready(function ($) {
	// Add more images dynamically
	$("#add-image").click(function () {
		$("#slider-images-container").append(
			'<div class="slider-image-row"><label for="myslider_image_url">Image URL:</label><input type="text" class="image-url" name="slider_image_url[]" placeholder="Image URL"><label for="myslider_tooltip_text">Tooltip Text:</label><input type="text" class="tooltip-text" name="slider_tooltip_text[]" placeholder="Tooltip Text"><button type="button" class="remove-image">Remove</button><div class="image-preview"></div></div>'
		);
	});

	// Remove image
	$(document).on("click", ".remove-image", function () {
		$(this).parent(".slider-image-row").remove();
	});

	// Update image preview
	$(document).on("input", ".image-url", function () {
		let imageUrl = $(this).val();
		let imagePreview = $(this).siblings(".image-preview");
		if (imageUrl) {
			imagePreview.html('<img src="' + imageUrl + '" alt="">');
		} else {
			imagePreview.empty();
		}
	});

	// Enable drag and drop sorting
	$("#slider-images-container").sortable({
		axis: "y", // Only allow vertical sorting
		handle: ".image-preview", // Use the image preview as the handle for dragging
		cursor: "move", // Change cursor to indicate drag-and-drop
		update: function (event, ui) {
			// When the sorting is updated, update the hidden input fields for image URLs and tooltip texts
			updateHiddenFields();
		},
	});

	// Function to update hidden input fields
	function updateHiddenFields() {
		$(".slider-image-row").each(function (index) {
			$(this)
				.find(".image-url")
				.attr("name", "slider_image_url[" + index + "]");
			$(this)
				.find(".tooltip-text")
				.attr("name", "slider_tooltip_text[" + index + "]");
		});
	}

	// Capture form submission event
	$("#post").submit(function (event) {
		// Check if the post status is "draft"
		if ($("#post_status").val() === "draft") {
			// Prevent the default form submission
			event.preventDefault();

			// Get the slider images and custom fields data
			let imageData = [];
			$(".image-url").each(function () {
				imageData.push($(this).val());
			});

			let tooltipData = [];
			$(".tooltip-text").each(function () {
				tooltipData.push($(this).val());
			});

			let postData = {
				action: "save_slider_data",
				post_id: $("#post_ID").val(),
				image_data: imageData,
				tooltip_data: tooltipData,
			};

			// Send AJAX request to save the data
			$.post(myslider_ajax_object.ajax_url, postData, function (response) {
				// After successful save, submit the form again to save other fields
				$("#post").unbind("submit").submit();
			});
		}
	});

	// Handle click event on delete slider link
	$(".delete-slider").on("click", function (e) {
		e.preventDefault();

		let sliderId = $(this).data("slider-id");

		// Confirm deletion
		if (confirm("Are you sure you want to delete this slider?")) {
			// Send AJAX request to delete slider
			$.ajax({
				url: myslider_ajax.ajax_url,
				type: "POST",
				data: {
					action: "delete_slider",
					slider_id: sliderId,
					nonce: myslider_ajax.nonce,
				},
				success: function (response) {
					// Reload the page after successful deletion
					location.reload();
				},
				error: function (xhr, status, error) {
					console.error(xhr.responseText);
				},
			});
		}
	});
});
