document.addEventListener("DOMContentLoaded", function () {
    const searchBtn = document.getElementById("searchBtn");
    const searchInput = document.getElementById("searchInput");

    searchBtn.addEventListener("click", function () {
        const keyword = searchInput.value.trim();
        if (keyword !== "") {
            // Redirect to the route with query parameter
            window.location.href = `/search-result?keyword=${encodeURIComponent(
                keyword
            )}`;
        }
    });

    // Optional: trigger search on Enter key
    searchInput.addEventListener("keydown", function (e) {
        if (e.key === "Enter") {
            searchBtn.click();
        }
    });
});
