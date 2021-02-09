$(function (){
    $("#departements").select2()
    $("#keywords").select2({
        tags: true,
        tokenSeparators: [",", " "]
    }).on("change", function (){
        let mot = $(this).find('[data-select2-tag="true"]:last-of-type')
        console.log(mot);
    })
})