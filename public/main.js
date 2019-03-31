var board_id;
var column_id;
var columnfield_id;

$(document).ready(function () {
    console.log("ready!");

    $('#howtoModal').modal();

    $('#start-game').click(function(){
        axios.get(url_start)
            .then(function (response) {
                // handle success
                console.log(response);

                // $('#start-div').hide();
                $('#control-div').removeClass('d-none');
            })
            .catch(function (error) {
                // handle error
                console.log(error);
            })
        }
    );

    $('#press-up').click(function(){
            axios.get(url_up)
                .then(function (response) {
                    console.log(response);
                })
                .catch(function (error) {console.log(error);})
        }
    );

    $('#press-left').click(function(){
            axios.get(url_left)
                .then(function (response) {
                    console.log(response);
                })
                .catch(function (error) {console.log(error);})
        }
    );

    $('#press-right').click(function(){
            axios.get(url_right)
                .then(function (response) {
                    console.log(response);
                })
                .catch(function (error) {console.log(error);})
        }
    );

    $('#press-down').click(function(){
            axios.get(url_down)
                .then(function (response) {
                    console.log(response);
                })
                .catch(function (error) {console.log(error);})
        }
    );

});

function loading() {

    $('#start-game').disable();
    // $('#start-game').disable();
    // $('#start-game').disable();
    // $('#start-game').disable();
    // $('#start-game').disable();
}

function done_loading() {


}
