$(document).ready(function(){
	$collapsed = true;

	$('#addNewEvent').unbind('click').click(function(e) {
		e.preventDefault();
		$collapsed = false;

		$('#add-event-select-type').slideToggle(100,function() {
			if($(this).is(":visible")){
				$('#addNewEvent').removeClass('green').addClass('inactive');
				$('#addNewEvent span.button-span-green').removeClass('button-span-green').addClass('button-span-inactive');
				$('#addNewEvent span.button-icon-green').removeClass('button-icon-green').addClass('button-icon-inactive');
			} else {
				$('#addNewEvent').removeClass('inactive').addClass('green');
				$('#addNewEvent span.button-span-inactive').removeClass('button-span-inactive').addClass('button-span-green');
				$('#addNewEvent span.button-icon-inactive').removeClass('button-icon-inactive').addClass('button-icon-green');
				$collapsed = true;
			}
			return false;
		});

		return false;
	});

	$('label').die('click').live('click',function() {
		if ($(this).prev().is('input:radio')) {
			$(this).prev().click();
		}
	});

	$('select.dropDownTextSelection').die('change').change(function() {
		if ($(this).val() != '') {
			var target_id = $(this).attr('id').replace(/^dropDownTextSelection_/,'');

			if ($('#'+target_id).text().length >0) {
				$('#'+target_id).text($('#'+target_id).text()+', ');
			}

			$('#'+target_id).text($('#'+target_id).text()+$(this).children('option:selected').text());

			$(this).children('option:selected').remove();
		}
	});
});

function selectSort(a, b) {		 
		if (a.innerHTML == rootItem) {
				return -1;		
		}
		else if (b.innerHTML == rootItem) {
				return 1;  
		}				
		return (a.innerHTML > b.innerHTML) ? 1 : -1;
};

var rootItem = null;

function sort_selectbox(element) {
	rootItem = element.children('option:first').text();
	element.append(element.children('option').sort(selectSort));
}
