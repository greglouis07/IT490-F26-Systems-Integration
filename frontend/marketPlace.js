
const walletBalance = document.getElementById("wallet-balance");

// ---------------------------------------------
// VIRTUAL WALLET
// ---------------------------------------------

// Load existing wallet balance or start with $1,000
let balance = Number(localStorage.getItem("pokemonWallet"));

if (localStorage.getItem("pokemonWallet") === null) {
    balance = 1000;
    localStorage.setItem("pokemonWallet", balance);
}

// Reset invalid stored balances
if (!Number.isFinite(balance) || balance < 0) {
    balance = 1000;
    localStorage.setItem("pokemonWallet", balance);
}

// Display the virtual wallet balance
function displayWallet() {
    walletBalance.textContent = "$" + balance.toFixed(2);
}

// Save wallet changes
function saveWallet() {
    localStorage.setItem("pokemonWallet", balance);
    displayWallet();
}

displayWallet();


// ---------------------------------------------
// SHARED PORTFOLIO DATABASE CONNECTION
// ---------------------------------------------

// Communicate with the same collection backend
// used by the Portfolio page
async function marketplaceCollectionRequest(action, cardId = null) {
    const response = await fetch("collection.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        credentials: "same-origin",
        body: JSON.stringify({
            action: action,
            cardId: cardId
        })
    });

    const data = await response.json();

    if (!response.ok || !data.success) {
        throw new Error(
            data.message || "Collection request failed."
        );
    }

    return data;
}


// ---------------------------------------------
// MARKETPLACE HTML ELEMENTS
// ---------------------------------------------

const marketplaceSearchForm = document.getElementById(
    "marketplace-search-form"
);

const marketplaceSearchInput = document.getElementById(
    "marketplace-search"
);

const marketplaceCards = document.getElementById(
    "marketplace-cards"
);

const marketplaceMessage = document.getElementById(
    "marketplace-message"
);

const sellCardsContainer = document.getElementById(
    "sell-cards"
);


// ---------------------------------------------
// DISPLAY OWNED CARDS IN THE MARKETPLACE
// ---------------------------------------------

// Load the user's owned cards from MySQL
async function displayPortfolioCardsForSale() {
    sellCardsContainer.textContent = "Loading your cards...";

    try {
        const data = await marketplaceCollectionRequest(
            "get_collection"
        );

        const ownedCards = data.collection;

        if (!Array.isArray(ownedCards)) {
            throw new Error("Invalid collection data.");
        }

        sellCardsContainer.innerHTML = "";

        // Show message when the Portfolio is empty
        if (ownedCards.length === 0) {
            sellCardsContainer.textContent =
                "You have no cards available to sell.";
            return;
        }

        // Display every card owned by the user
        ownedCards.forEach(card => {
            const cardElement = document.createElement("div");

            // Card name and quantity
            const name = document.createElement("h3");

            name.textContent =
                card.name + " x" + card.quantity;

            // Card image
            const image = document.createElement("img");

            image.src = card.image
                ? (
                    /\.(png|jpe?g|webp|gif)(\?|$)/i.test(card.image)
                        ? card.image
                        : card.image + "/low.webp"
                )
                : "https://placehold.co/180x250?text=No+Image";

            image.alt = card.name;
            image.width = 120;

            image.onerror = function () {
                this.onerror = null;
                this.src =
                    "https://placehold.co/180x250?text=No+Image";
            };

            // Card market price
            const price = document.createElement("p");

            const marketPrice = Number(card.market_price);

            price.textContent =
                Number.isFinite(marketPrice) && marketPrice > 0
                    ? "Market Price: $" + marketPrice.toFixed(2)
                    : "Market Price: Unavailable";

            // Sell button
            // Disabled until the backend supports
            // removing exactly one copy of a card
            const sellButton = document.createElement("button");

            sellButton.textContent = "Sell Card";
            sellButton.disabled = true;

            sellButton.title =
                "Selling will be enabled after the backend " +
                "supports removing one card at a time.";

            // Add card information to the page
            cardElement.append(
                name,
                image,
                price,
                sellButton
            );

            sellCardsContainer.appendChild(cardElement);
        });

    } catch (error) {
        sellCardsContainer.textContent =
            "Unable to load your collection.";

        console.error(
            "Portfolio loading error:",
            error
        );
    }
}

// Display owned cards when Marketplace loads
displayPortfolioCardsForSale();


// ---------------------------------------------
// SEARCH FOR POKEMON CARDS
// ---------------------------------------------

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
            throw new Error(
                result.message || "Could not retrieve cards."
            );
        }

        if (!Array.isArray(result.data)) {
            throw new Error("Invalid card search response.");
        }

        const cards = result.data;

        marketplaceMessage.textContent = cards.length
            ? `Found ${cards.length} cards.`
            : "No Pokemon cards found.";

        // Display the first 30 results
        cards.slice(0, 30).forEach(card => {
            const cardElement = document.createElement("div");

            // Pokemon card name
            const name = document.createElement("h3");
            name.textContent = card.name;

            // Pokemon card image
            const image = document.createElement("img");

            image.src = card.image_url
                ? (
                    /\.(png|jpe?g|webp|gif)(\?|$)/i.test(card.image_url)
                        ? card.image_url
                        : card.image_url + "/low.webp"
                )
                : "https://placehold.co/180x250?text=No+Image";

            image.alt = card.name;
            image.width = 120;

            image.onerror = function () {
                this.onerror = null;
                this.src =
                    "https://placehold.co/180x250?text=No+Image";
            };

            // Get the card's market price
            const marketPrice = Number(card.market_price);

            const validPrice =
                card.market_price !== null &&
                card.market_price !== undefined &&
                card.market_price !== "" &&
                Number.isFinite(marketPrice) &&
                marketPrice > 0;

            // Display the purchase price
            const price = document.createElement("p");

            price.textContent = validPrice
                ? "Price: $" + marketPrice.toFixed(2)
                : "Price unavailable";

            // Create Buy Card button
            const buyButton = document.createElement("button");
            buyButton.textContent = "Buy Card";

            // Prevent purchases without a valid price
            buyButton.disabled = !validPrice;

            // ---------------------------------------------
            // BUY CARD FUNCTIONALITY
            // ---------------------------------------------

            buyButton.addEventListener("click", async () => {
                // Check if the user can afford the card
                if (balance < marketPrice) {
                    alert(
                        "Insufficient funds to purchase this card."
                    );
                    return;
                }

                // Verify the card has a valid MySQL card ID
                const cardId = Number(card.id);

                if (
                    !Number.isSafeInteger(cardId) ||
                    cardId <= 0
                ) {
                    alert(
                        "This card cannot be purchased."
                    );
                    return;
                }

                // Prevent duplicate clicks while purchasing
                buyButton.disabled = true;

                try {
                    // Add one copy to the shared MySQL collection
                    await marketplaceCollectionRequest(
                        "add_to_collection",
                        cardId
                    );

                } catch (error) {
                    alert(
                        "Purchase failed: " + error.message
                    );

                    console.error(
                        "Marketplace purchase error:",
                        error
                    );

                    return;

                } finally {
                    buyButton.disabled = false;
                }

                // Deduct the price after database success
                balance = Math.round(
                    (balance - marketPrice) * 100
                ) / 100;

                saveWallet();

                // Reload the owned cards from MySQL
                await displayPortfolioCardsForSale();

                // Confirm the purchase
                alert(
                    `Successfully purchased ${card.name} ` +
                    `for $${marketPrice.toFixed(2)}!`
                );
            });

            // Display card and Buy button
            cardElement.append(
                name,
                image,
                price,
                buyButton
            );

            marketplaceCards.appendChild(cardElement);
        });

    } catch (error) {
        marketplaceMessage.textContent =
            `Search failed: ${error.message}`;

        console.error(
            "Marketplace search error:",
            error
        );
    }
}


// ---------------------------------------------
// HANDLE MARKETPLACE SEARCH
// ---------------------------------------------

marketplaceSearchForm.addEventListener(
    "submit",
    event => {
        event.preventDefault();

        const query = marketplaceSearchInput.value.trim();

        if (query) {
            searchMarketplaceCards(query);
        }
    }
);
