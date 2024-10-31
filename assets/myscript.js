window.addEventListener("load", function() {
    // store the parent element of tabs
    var tabContainer = document.querySelector("ul.nav-tabs");

    tabContainer.addEventListener("click", function(event) {
        if (event.target && event.target.nodeName === "A") {
            event.preventDefault();
            
            document.querySelector("ul.nav-tabs li.active").classList.remove("active");
            document.querySelector(".tab-pane.active").classList.remove("active");

            var clickedTab = event.target.parentElement;
            var activePaneID = event.target.getAttribute("href");

            clickedTab.classList.add("active");
            document.querySelector(activePaneID).classList.add("active");
        }
    });
});
