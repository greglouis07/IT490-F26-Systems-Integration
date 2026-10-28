
const searchForm = document.getElementById("card-search-form");
const searchInput = document.getElementById("pokemon-search");
const searchResults = document.getElementById("card-results");
const searchMessage = document.getElementById("search-message");

const collectionContainer = document.getElementById("my-collection");
const collectionValue = document.getElementById("collection-value");

// glb9 Watchlist container
const watchlistContainer = document.getElementById("my-watchlist");

let collection = [];


// ---------------------------------------------
// COLLECTION FUNCTIONS
// ---------------------------------------------

async function collectionRequest(action, cardId) {
    const response = await fetch("collection.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        credentials: "same-origin",
        body: JSON.stringify({ action, cardId })
    });

    return response.json();
}


// Load collection from database
async function loadCollection() {
    const data = await collectionRequest("get_collection");

    if (data.success) {
        collection = data.collection;
    }

    displayCollection();
}


// Display collection on homepage
function displayCollection() {
    collectionContainer.innerHTML = "";

    if (collection.length === 0) {
        collectionContainer.textContent =
            "No Pokémon cards added yet.";
    }

    collection.forEach(card => {
        const cardElement = document.createElement("div");

        const name = document.createElement("h3");
        name.textContent = card.name + " x" + card.quantity;

        const image = document.createElement("img");

        image.src = card.image
            ? (card.image.includes("assets.tcgdex.net")
                ? card.image + "/low.webp"
                : card.image)
            : "https://placehold.co/180x250?text=No+Image";

        image.alt = card.name;
        image.width = 120;

        image.onerror = function () {
            this.onerror = null;
            this.src = "https://placehold.co/180x250?text=No+Image";
        };

        const removeButton = document.createElement("button");
        removeButton.textContent = "Remove";

        removeButton.addEventListener("click", async () => {
            await collectionRequest(
                "remove_from_collection",
                card.id
            );

            loadCollection();
        });

        cardElement.append(name, image, removeButton);
        collectionContainer.appendChild(cardElement);
    });

    collectionValue.textContent = "$0.00";
}


// ---------------------------------------------
// WATCHLIST FUNCTIONS
// ---------------------------------------------

// glb9 Watchlist requests go through watchlist.php
async function watchlistRequest(action, cardId, targetPrice) {
    const response = await fetch("watchlist.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        credentials: "same-origin",
        body: JSON.stringify({
            action,
            cardId,
            targetPrice
        })
    });

    return response.json();
}


// Load saved Watchlist cards from the database
async function loadWatchlist() {
    try {
        const data = await watchlistRequest("get_watchlist");

        displayWatchlist(
            data.success ? data.watchlist : []
        );

    } catch (error) {
        console.error("Watchlist loading error:", error);

        watchlistContainer.textContent =
            "Unable to load your watchlist.";
    }
}


// Display cards currently being watched
function displayWatchlist(watchlist) {
    watchlistContainer.innerHTML = "";

    if (!Array.isArray(watchlist) || watchlist.length === 0) {
        watchlistContainer.textContent =
            "No cards on your watchlist yet.";
        return;
    }

    watchlist.forEach(card => {
        const cardElement = document.createElement("div");

        // Pokemon card name
        const name = document.createElement("h3");
        name.textContent = card.name;

        // Pokemon card image
        const image = document.createElement("img");

        image.src = card.image_url ||
            "https://placehold.co/180x250?text=No+Image";

        image.alt = card.name;
        image.width = 120;

        image.onerror = function () {
            this.onerror = null;
            this.src = "https://placehold.co/180x250?text=No+Image";
        };

        // Current market price and target price
        const prices = document.createElement("p");

        prices.textContent =
            "Current: $" + (card.market_price ?? "N/A") +
            " | Target: $" + card.target_price +
            (card.alerted_at ? " | Alert sent" : "");

        // Stop Watching button
        const removeButton = document.createElement("button");
        removeButton.textContent = "Stop Watching";

        removeButton.addEventListener("click", async () => {
            try {
                const data = await watchlistRequest(
                    "remove_watch",
                    card.card_id
                );

                if (!data.success) {
                    alert(
                        data.message ||
                        "Could not remove card from watchlist."
                    );
                    return;
                }

                loadWatchlist();

            } catch (error) {
                console.error("Watchlist removal error:", error);
                alert("Could not remove card from watchlist.");
            }
        });

        cardElement.append(
            name,
            image,
            prices,
            removeButton
        );

        watchlistContainer.appendChild(cardElement);
    });
}


// ---------------------------------------------
// SEARCH POKEMON CARDS
// ---------------------------------------------

async function searchCards(query) {
    searchMessage.textContent = "Searching...";
    searchResults.innerHTML = "";

    try {
        const response = await fetch("cardSearch.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            credentials: "same-origin",
            body: JSON.stringify({
                cardName: query
            })
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(
                result.message || "Could not retrieve cards."
            );
        }

        if (!Array.isArray(result.data)) {
            throw new Error("Invalid card search response.");
        }

        const cards = result.data;

        searchMessage.textContent = cards.length
            ? `Found ${cards.length} cards.`
            : "No Pokémon cards found.";

        // Display first 30 search results
        cards.slice(0, 30).forEach(card => {
            const cardElement = document.createElement("div");

            // Card name
            const name = document.createElement("h3");
            name.textContent = card.name;

            // Card image
            const image = document.createElement("img");

            image.src = card.image_url ||
                "https://placehold.co/180x250?text=No+Image";

            image.alt = card.name;
            image.width = 120;

            image.onerror = function () {
                this.onerror = null;
                this.src =
                    "https://placehold.co/180x250?text=No+Image";
            };


            // ---------------------------------------------
            // ADD TO COLLECTION BUTTON
            // ---------------------------------------------

            const addButton = document.createElement("button");
            addButton.textContent = "Add to Collection";

            addButton.addEventListener("click", async () => {
                const data = await collectionRequest(
                    "add_to_collection",
                    card.id
                );

                if (!data.success) {
                    alert(
                        data.message || "Could not add the card."
                    );
                    return;
                }

                loadCollection();
            });


            // ---------------------------------------------
            // WATCH PRICE BUTTON
            // ---------------------------------------------

            // glb9 Watch button: asks for target price,
            // saves it using add_watch
            const watchButton = document.createElement("button");
            watchButton.textContent = "Watch Price";

            watchButton.addEventListener("click", async () => {
                const targetPrice = prompt(
                    "Alert me when " +
                    card.name +
                    " is at or below ($):"
                );

                // User canceled or entered nothing
                if (
                    targetPrice === null ||
                    targetPrice.trim() === ""
                ) {
                    return;
                }

                try {
                    const data = await watchlistRequest(
                        "add_watch",
                        card.id,
                        targetPrice.trim()
                    );

                    if (!data.success) {
                        alert(
                            data.message ||
                            "Could not add to watchlist."
                        );
                        return;
                    }

                    // Refresh watchlist after adding card
                    loadWatchlist();

                } catch (error) {
                    console.error("Watchlist add error:", error);
                    alert("Could not add card to watchlist.");
                }
            });


            // ---------------------------------------------
            // OPEN CARD DETAILS
            // ---------------------------------------------

            // Open details when card itself is clicked
            cardElement.style.cursor = "pointer";

            cardElement.addEventListener("click", event => {
                // Don't navigate when a button is clicked
                if (event.target.closest("button")) {
                    return;
                }

                sessionStorage.setItem(
                    "selectedPokemonCard",
                    JSON.stringify(card)
                );

                window.location.href = "cardDetails.html";
            });


            // Display card with BOTH buttons
            cardElement.append(
                name,
                image,
                addButton,
                watchButton
            );

            searchResults.appendChild(cardElement);
        });

    } catch (error) {
        searchMessage.textContent =
            `Search failed: ${error.message}`;

        console.error("Card search error:", error);
    }
}


// ---------------------------------------------
// HANDLE SEARCH FORM
// ---------------------------------------------

searchForm.addEventListener("submit", event => {
    event.preventDefault();

    const query = searchInput.value.trim();

    if (query) {
        searchCards(query);
    }
});


// ---------------------------------------------
// LOAD COLLECTION AND WATCHLIST
// ---------------------------------------------

loadCollection();
loadWatchlist();


// ---------------------------------------------
// SESSION VALIDATION
// ---------------------------------------------

// Check whether the user has a valid session
async function validateSession() {
    try {
        const response = await fetch("auth.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            credentials: "same-origin",
            body: JSON.stringify({
                type: "validate_session"
            })
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            window.location.replace("index.html");
        }

    } catch (error) {
        console.error("Session validation failed:", error);
        window.location.replace("index.html");
    }
}


// ---------------------------------------------
// LOG OUT
// ---------------------------------------------

async function logout() {
    try {
        const response = await fetch("auth.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            credentials: "same-origin",
            body: JSON.stringify({
                type: "logout"
            })
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            console.error("Logout failed:", data.message);
            return;
        }

        window.location.replace("index.html");

    } catch (error) {
        console.error("Logout error:", error);
    }
}


// Validate the user's session
validateSession();


// Handle logout link
document.getElementById("logout-link").addEventListener(
    "click",
    function (event) {
        event.preventDefault();
        logout();
    }
);
