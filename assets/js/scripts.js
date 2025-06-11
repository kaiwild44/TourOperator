// Hamburger Menu Animation
document.getElementById('hamburger').addEventListener('click', function() {
    var icon = document.getElementById('icon');
    if (icon.classList.contains('fa-bars')) {
        icon.classList.remove('fa-bars');
        icon.classList.add('fa-times');
    } else {
        icon.classList.remove('fa-times');
        icon.classList.add('fa-bars');
    }
});

// Tooltips for messengers, note, hotel room numbers
function toggleTooltip(iconElement, tooltipId) {
    var tooltip = document.getElementById(tooltipId);
    if (tooltip.style.display === 'block') {
        tooltip.style.display = 'none';
    } else {
        tooltip.style.display = 'block';
    }
}

  // Hide the filter section initially
  document.getElementById('filter-section').style.display = 'none';

  // Toggle visibility of the filter section on button click
  document.getElementById('filters-btn').addEventListener('click', function() {
    const filterSection = document.getElementById('filter-section');
    if (filterSection.style.display === 'none') {
      filterSection.style.display = 'block';
    } else {
      filterSection.style.display = 'none';
    }
  });

  // Capture the change event on the month filter dropdown
document.getElementById('month_filter').addEventListener('change', function() {
    fetchFilteredBookings();
});

// Filter inputs
document.getElementById('first_name').addEventListener('input', fetchFilteredBookings);
document.getElementById('last_name').addEventListener('input', fetchFilteredBookings);
document.getElementById('tour_name').addEventListener('input', fetchFilteredBookings);
document.getElementById('agent_first_name').addEventListener('input', fetchFilteredBookings);
document.getElementById('agent_last_name').addEventListener('input', fetchFilteredBookings);
document.getElementById('tour_lang').addEventListener('input', fetchFilteredBookings);

// Function to fetch filtered bookings
function fetchFilteredBookings() {
    var firstName = document.getElementById('first_name').value;
    var lastName = document.getElementById('last_name').value;
    var tourName = document.getElementById('tour_name').value;
    var agentFirstName = document.getElementById('agent_first_name').value;
    var agentLastName = document.getElementById('agent_last_name').value;
    var tourLang = document.getElementById('tour_lang').value; 
    var selectedMonths = document.getElementById('month_filter').value;

    // Log the inputs for debugging
    console.log("Fetching bookings with filters:", {
        first_name: firstName,
        last_name: lastName,
        tour_name: tourName,
        tour_lang: tourLang,
        agent_first_name: agentFirstName,
        agent_last_name: agentLastName,
        months: selectedMonths
    });

    $.ajax({
        url: 'fetch_bookings.php',
        method: 'POST',
        data: {
            first_name: firstName,
            last_name: lastName,
            tour_name: tourName,
            tour_lang: tourLang,
            agent_first_name: agentFirstName,
            agent_last_name: agentLastName,
            months: selectedMonths
        },
        success: function(response) {
            $('#table-data').html(response); // Reload the table with the response
        },
        error: function(xhr, status, error) {
            console.error('Error loading data: ', error);
        }
    });
}

// Clear Filters button event
document.getElementById('clear-filters-btn').addEventListener('click', function() {
    // Clear all filter inputs
    document.getElementById('first_name').value = '';
    document.getElementById('last_name').value = '';
    document.getElementById('tour_name').value = '';
    document.getElementById('tour_lang').value = '';
    document.getElementById('agent_first_name').value = '';
    document.getElementById('agent_last_name').value = '';
    document.getElementById('date_range').value = '';
    document.getElementById('month_filter').selectedIndex = 0; // Reset to first option

    // Call the fetch function to refresh the table
    fetchFilteredBookings(); // Fetch all bookings without filters
});