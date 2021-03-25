function showMore(id) {
    let div = document.getElementById("matches__match__more_" + id);
    if(div.style.display === "block") {
        div.style.display = "none"
        return
    }
    div.style.display = "block"
}