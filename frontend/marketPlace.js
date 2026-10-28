
const walletBalance = document.getElementById("wallet-balance");

// Load existing balance or give the user $1,000
let balance = localStorage.getItem("pokemonWallet");

if (balance === null) {
    balance = 1000;
    localStorage.setItem("pokemonWallet", balance);
} else {
    balance = Number(balance);
}

// Display the current wallet balance
function displayWallet() {
    walletBalance.textContent = "$" + balance.toFixed(2);
}

// Save changes to the wallet
function saveWallet() {
    localStorage.setItem("pokemonWallet", balance);
    displayWallet();
}

// Display wallet when the page loads
displayWallet();

const marketplaceSearchForm = document.getElementById("marketplace-search-form");
const marketplaceSearchInput = document.getElementById("marketplace-search");
const marketplaceCards = document.getElementById("marketplace-cards");
const marketplaceMessage = document.getElementById("marketplace-message");

// Search for Pokemon cards available to purchase
async function searchMarketplaceCards(query) {
    marketplaceMessage.textContent = "Searching...";
    marketplaceCards.innerHTML = "";

    try {
        const response = await fetch("cardSearch.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            credentials: "same-origin",
            body: JSON.stringify({
                cardName: query
            })
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || "Could not retrieve cards.");
        }

        if (!Array.isArray(result.data)) {
            throw new Error("Invalid card search response.");
        }

        const cards = result.data;

        marketplaceMessage.textContent = cards.length
            ? `Found ${cards.length} cards.`
            : "No Pokemon cards found.";

        cards.slice(0, 30).forEach(card => {
            const cardElement = document.createElement("div");

            const name = document.createElement("h3");
            name.textContent = card.name;

            const image = document.createElement("img");
            image.src = card.image_url ||
                "https://placehold.co/180x250?text=No+Image";
            image.alt = card.name;
            image.width = 120;

            image.onerror = function () {
                this.onerror = null;
                this.src = "https://placehold.co/180x250?text=No+Image";
            };

            const price = document.createElement("p");
            const marketPrice = Number(card.market_price);

            price.textContent =
                Number.isFinite(marketPrice) && marketPrice > 0
                    ? "Price: $" + marketPrice.toFixed(2)
                    : "Price unavailable";

            cardElement.append(name, image, price);
            marketplaceCards.appendChild(cardElement);
        });

    } catch (error) {
        marketplaceMessage.textContent =
            `Search failed: ${error.message}`;

        console.error("Marketplace search error:", error);
    }
}

// Handle Marketplace search form
marketplaceSearchForm.addEventListener("submit", event => {
    event.preventDefault();

    const query = marketplaceSearchInput.value.trim();

    if (query) {
        searchMarketplaceCards(query);
    }
});

