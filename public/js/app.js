document.addEventListener('DOMContentLoaded', function() {
    const searchButton = document.getElementById('search-button');
    const resultsContainer = document.getElementById('results');
    
    searchButton.addEventListener('click', function() {
        const make = document.getElementById('make').value;
        const model_year = document.getElementById('model_year').value;
        const registration_number = document.getElementById('registration_number').value;
        
        const params = new URLSearchParams();
        if (make) params.append('make', make);
        if (model_year) params.append('model_year', model_year);
        if (registration_number) params.append('registration_number', registration_number);
        
        resultsContainer.innerHTML = '<p>Laddar...</p>';

        fetch(`../src/routes/search.php?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayResults(data.data);
                } else {
                    resultsContainer.innerHTML = `<p class="error">Ett fel uppstod: ${data.error}</p>`;
                }
            })
            .catch(error => {
                resultsContainer.innerHTML = `<p class="error">Ett fel uppstod: ${error.message}</p>`;
            });
    });
    
    function displayResults(cars) {
        if (cars.length === 0) {
            resultsContainer.innerHTML = '<div class="no-results">Inga bilar hittades med dina sökkriterier.</div>';
            return;
        }
        
        let html = '';
        cars.forEach(car => {
            html += `
                <div class="car-card">
                    <div class="car-image">
                        <img src="${car.image_url || 'https://via.placeholder.com/200x150?text=No+Image'}" alt="${car.title}">
                    </div>
                    <div class="car-info">
                        <h2>${car.title}</h2>
                        <p><strong>År:</strong> ${car.model_year}</p>
                        <p><strong>Registreringsnummer:</strong> ${car.registration_number || 'Okänt'}</p>
                        <p><strong>Registreringsnummer (Scannad från bild):</strong> ${car.registration_number_from_image || 'Okänt'}</p>
                        <p><a href="${car.page_url}" target="_blank">Visa på Bilweb</a></p>
                    </div>
                </div>
            `;
        });
        
        html = `<p>Hittade ${cars.length} bilar:</p>` + html;
        
        resultsContainer.innerHTML = html;
    }
});
