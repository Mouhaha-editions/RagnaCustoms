const searchInput = document.getElementById('search');
const form = document.querySelector('.search-and-filter');
const selects = form.querySelectorAll('select');

//add event listener for keyup on search input, debounce to avoid calls on each event and then search
searchInput.addEventListener('keyup', 
debounceSearch(()=>{
  doSearch();
}, 400));

//debounce the search to it only runs every 400ms instead of every keypress
function debounceSearch(fn, ms) {
    let timer;
    return function(...args) {
      clearTimeout(timer)
      timer = setTimeout(fn.bind(this, ...args), ms || 0)
    }
  }

  //ajax search function
function doSearch(){
  //build a string of url query params from the form elements
    const queryParams = '?'+[...form.elements].filter(el => {
      return el.value && (el.type !== 'submit')}).map(x => {
        return `${x.name}=${x.value}`
      }).join("&")+"&type=ajax";
      //show the spinner
    document.querySelector('.loading-spinner').style.display = "grid"
    //do the search
    fetch(`/${queryParams}`)
    .then(resp => resp.json())
    .then(results => {
      //display results
        const html = results.html;
        document.querySelector('.song-container').innerHTML = html;
        //hide spinner
        document.querySelector('.loading-spinner').style.display = "none"
    })
}

//add listener to search on form select change
selects.forEach(el => {
  el.addEventListener('change', function(){
    doSearch();
  })
});
