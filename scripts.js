const ratingSlider = document.getElementById("filter-rating");
const ratingValue = document.getElementById("rating-value");

if (ratingSlider && ratingValue) {
  const syncRatingValue = () => {
    ratingValue.textContent = Number(ratingSlider.value).toFixed(1);
  };

  syncRatingValue();
  ratingSlider.addEventListener("input", syncRatingValue);
}

document.querySelectorAll("form[data-confirm]").forEach((form) => {
  form.addEventListener("submit", (event) => {
    const message = form.getAttribute("data-confirm") || "Potvrdite akciju.";

    if (!window.confirm(message)) {
      event.preventDefault();
    }
  });
});
