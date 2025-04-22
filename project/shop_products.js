        // Simple Interactivity for Pagination
        const pageButtons = document.querySelectorAll('.pagination span');
        pageButtons.forEach(button => {
            button.addEventListener('click', () => {
                document.querySelector('.pagination span.active').classList.remove('active');
                button.classList.add('active');
            });
        });

        // Filter Buttons Active State
        const filterButtons = document.querySelectorAll('.filter-buttons button');
        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                document.querySelector('.filter-buttons button.active').classList.remove('active');
                button.classList.add('active');
            });
        });

        // Variables
        //const filterButtons = document.querySelectorAll('#filters button');
        const cardsContainer = document.getElementById('card-container');
        const cards = Array.from(cardsContainer.getElementsByClassName('card'));
        const paginationContainer = document.getElementById('pagination');
        const prevPageButton = document.getElementById('prev-page');
        const nextPageButton = document.getElementById('next-page');
        const pageNumbers = document.getElementById('page-numbers');
        
        let currentPage = 1;
        const cardsPerPage = (document.getElementsByTagName('title')[0].innerText=='Shop')? 8: 6;
        
        // Function to display cards based on the selected filter
        function filterCards(category) {
          currentPage = 1; // Reset to first page
          cards.forEach(card => {
            card.style.display = (category === 'all' || card.classList.contains(category)) ? 'grid' : 'none';
          });
          filterResult();
        }
        function filterResult() {
            const visibleCards = cards.filter(card => card.style.display !== 'none');
            const totalPages = Math.ceil(visibleCards.length / cardsPerPage);
            
            // Hide all cards initially
            visibleCards.forEach(card => card.style.display = 'none');
            
            // Display only the cards for the current page
            const start = (currentPage - 1) * cardsPerPage;
            const end = start + cardsPerPage;
            visibleCards.slice(start, end).forEach(card => card.style.display = 'block');

            updatePaginationUI(totalPages);
        }
        
        // Function to paginate cards
        function paginateCards() {
          const allCards = cards;
          const visibleCards = cards.filter(card => card.style.display !== 'none');
          const totalPages = Math.ceil(allCards.length / cardsPerPage);
        
          // Hide all cards initially
          visibleCards.forEach(card => card.style.display = 'none');
        
          // Display only the cards for the current page
          const start = (currentPage - 1) * cardsPerPage;
          const end = start + cardsPerPage;
          allCards.slice(start, end).forEach(card => card.style.display = 'block');

          updatePaginationUI(totalPages);
        }
        
        // Function to update pagination controls
        function updatePaginationUI(totalPages) {
          // Update page numbers
          pageNumbers.innerHTML = '';
          for (let i = 1; i <= totalPages; i++) {
            const pageButton = document.createElement('button');
            pageButton.classList.add(`page${i}`);
            pageButton.textContent = i;
            pageButton.disabled = i === currentPage;
            pageButton.addEventListener('click', () => {
              currentPage = i;
              paginateCards();
            });
            pageNumbers.appendChild(pageButton);
          }
          pageNumbers.getElementsByClassName(`page${currentPage}`)[0].style = "background-color: var(--secondary-color);color: white;";

          // Update prev/next button states
          prevPageButton.disabled = currentPage === 1;
          nextPageButton.disabled = currentPage === totalPages;
        }
        
        // Event Listeners for Filter Buttons
        filterButtons.forEach(button => {
          button.addEventListener('click', () => {
            const category = button.getAttribute('data-category');
            filterCards(category);
          });
        });
        
        // Event Listeners for Pagination
        prevPageButton.addEventListener('click', () => {
          if (currentPage > 1) {
            currentPage--;
            paginateCards();
          }
        });
        
        nextPageButton.addEventListener('click', () => {
          const totalPages = Math.ceil(cards.filter(card => card.style.display !== 'none').length / cardsPerPage);
          if (currentPage < totalPages) {
            currentPage++;
            paginateCards();
          }
        });
        
        // Initialize
        filterCards('all'); // Display all cards by default