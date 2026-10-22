
const searchForm = document.getElementById("card-search-form");
const searchInput = document.getElementById("pokemon-search");
const searchResults = document.getElementById("card-results");
const searchMessage = document.getElementById("search-message");
const collectionContainer = document.getElementById("my-collection");
const collectionValue = document.getElementById("collection-value");

const API_URL = "https://api.tcgdex.net/v2/en";

let collection = JSON.parse(
    localStorage.getItem("pokemonCollection") || "[]"
);

function saveCollection() {
    localStorage.setItem(
        "pokemonCollection",
        JSON.stringify(collection)
    );
}

function displayCollection() {
    collectionContainer.innerHTML = "";

    if (collection.length === 0) {
        collectionContainer.textContent =
            "No Pokémon cards added yet.";
    }

    collection.forEach(card => {
        const cardElement = document.createElement("div");

        const name = document.createElement("h3");
        name.textContent = card.name;

        const image = document.createElement("img");
        image.src = card.image + "/low.webp";
        image.alt = card.name;
        image.width = 120;

        const removeButton = document.createElement("button");
        removeButton.textContent = "Remove";

        removeButton.addEventListener("click", () => {
            collection = collection.filter(
                item => item.id !== card.id
            );

            saveCollection();
            displayCollection();
        });

        cardElement.append(name, image, removeButton);
        collectionContainer.appendChild(cardElement);
    });

    // Real prices will be connected in a later step.
    collectionValue.textContent = "$0.00";
}

async function searchCards(query) {
    searchMessage.textContent = "Searching...";
    searchResults.innerHTML = "";

    try {
        const response = await fetch(
            `${API_URL}/cards?name=${encodeURIComponent(query)}`
        );

        if (!response.ok) {
            throw new Error("Could not retrieve cards.");
        }

        const cards = await response.json();

        searchMessage.textContent = cards.length
            ? `Found ${cards.length} cards.`
            : "No Pokémon cards found.";

        cards.slice(0, 30).forEach(card => {
            const cardElement = document.createElement("div");

            const name = document.createElement("h3");
            name.textContent = card.name;

            const image = document.createElement("img");
            if (card.image) {
                image.src = card.image + "/low.webp";
                image.alt = card.name;
                image.width = 120;
            }

            const addButton = document.createElement("button");
            addButton.textContent = "Add to Collection";

            addButton.addEventListener("click", () => {
                const alreadyAdded = collection.some(
                    item => item.id === card.id
                );

                if (alreadyAdded) {
                    alert("This card is already in your collection.");
                    return;
                }

                collection.push({
                    id: card.id,
                    name: card.name,
                    image: card.image || ""
                });

                saveCollection();
                displayCollection();
            });

            cardElement.append(name);

            if (card.image) {
                cardElement.appendChild(image);
            }

            cardElement.appendChild(addButton);
            searchResults.appendChild(cardElement);
        });

    } catch (error) {
        searchMessage.textContent =
            "Search failed. Please try again.";
        console.error(error);
    }
}

searchForm.addEventListener("submit", event => {
    event.preventDefault();

    const query = searchInput.value.trim();

    if (query) {
        searchCards(query);
    }
});

displayCollection();

// Check if the user has a valid session
async function validateSession() {
    try {
        const response = await fetch("auth.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            credentials: "same-origin",
            body: JSON.stringify({ type: "validate_session" })
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

// Log the user out
async function logout() {
    try {
        const response = await fetch("auth.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            credentials: "same-origin",
            body: JSON.stringify({ type: "logout" })
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

// validate session on homePage
validateSession();

// Handle logout link
document.getElementById("logout-link").addEventListener("click", function (event) {
    event.preventDefault();
    logout();
});
