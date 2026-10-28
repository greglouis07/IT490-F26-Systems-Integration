
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

// Load cards purchased through the Marketplace
let purchasedCards = JSON.parse(
    localStorage.getItem("marketplacePurchasedCards") || "[]"
);

// Save purchased cards
function savePurchasedCards() {
    localStorage.setItem(
        "marketplacePurchasedCards",
        JSON.stringify(purchasedCards)
    );
}

// Marketplace search elements
const marketplaceSearchForm = document.getElementById("marketplace-search-form");
const marketplaceSearchInput = document.getElementById("marketplace-search");
const marketplaceCards = document.getElementById("marketplace-cards");
const marketplaceMessage = document.getElementById("marketplace-message");

// Container for cards available to sell
const sellCardsContainer = document.getElementById("sell-cards");

// Display purchased cards in the Sell My Cards section
function displaySellCards() {
    sellCardsContainer.innerHTML = "";

    if (purchasedCards.length === 0) {
        sellCardsContainer.textContent = "You have no cards available to sell.";
        return;
    }

    purchasedCards.forEach(card => {
        const cardElement = document.createElement("div");

        const name = document.createElement("h3");
        name.textContent = card.name;

        const image = document.createElement("img");
        image.src = card.image ||
            "https://placehold.co/180x250?text=No+Image";
        image.alt = card.name;
        image.width = 120;

        image.onerror = function () {
            this.onerror = null;
            this.src = "https://placehold.co/180x250?text=No+Image";
        };

        const price = document.createElement("p");
        price.textContent = "Market Price: $" +
            Number(card.market_price).toFixed(2);

        // Create Sell Card button
        const sellButton = document.createElement("button");
        sellButton.textContent = "Sell Card";

        sellButton.addEventListener("click", () => {
            const sellingPrice = Number(card.market_price);

            // Make sure the selling price is valid
            if (!Number.isFinite(sellingPrice) || sellingPrice <= 0) {
                alert("This card cannot be sold because its price is unavailable.");
                return;
            }

            // Find the exact purchased copy
            const cardIndex = purchasedCards.indexOf(card);

            if (cardIndex === -1) {
                alert("You do not own this card.");
                return;
            }

            // Remove one purchased copy
            purchasedCards.splice(cardIndex, 1);
            savePurchasedCards();

            // Add the selling price to the wallet
            balance = Math.round((balance + sellingPrice) * 100) / 100;
            saveWallet();

            // Refresh the Sell My Cards section
            displaySellCards();

            // Confirm the sale
            alert(`Successfully sold ${card.name} for $${sellingPrice.toFixed(2)}!`);
        });

        cardElement.append(name, image, price, sellButton);
        sellCardsContainer.appendChild(cardElement);
    });
}

// Display purchased cards when the page loads
displaySellCards();

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

            // Create Buy Card button
            const buyButton = document.createElement("button");
            buyButton.textContent = "Buy Card";

            // Disable buying if the price is unavailable
            buyButton.disabled =
                !Number.isFinite(marketPrice) || marketPrice <= 0;

            buyButton.addEventListener("click", () => {

                // Check if the user has enough money
                if (balance < marketPrice) {
                    alert("Insufficient funds to purchase this card.");
                    return;
                }

                // Save the purchased card
                purchasedCards.push({
                    id: card.id,
                    name: card.name,
                    image: card.image_url || "",
                    set_name: card.set_name,
                    number: card.number,
                    market_price: marketPrice
                });

                savePurchasedCards();

                // Deduct the card price from the wallet
                balance = Math.round((balance - marketPrice) * 100) / 100;
                saveWallet();

                // Refresh the Sell My Cards section
                displaySellCards();

                // Confirm the purchase
                alert(`Successfully purchased ${card.name} for $${marketPrice.toFixed(2)}!`);
            });

            cardElement.append(name, image, price, buyButton);
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
