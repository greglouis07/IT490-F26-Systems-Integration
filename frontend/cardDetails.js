// Retrieve the selected Pokémon card
const savedCard = sessionStorage.getItem("selectedPokemonCard");

if (!savedCard) {
    document.getElementById("card-name").textContent =
        "No Pokémon card selected.";
} else {
    try {
        const card = JSON.parse(savedCard);

        // Card name
        document.getElementById("card-name").textContent =
            card.name || "Unknown Pokémon";

        // Card image
        const cardImage = document.getElementById("card-image");

        cardImage.src = card.image_url ||
            "https://placehold.co/300x420?text=No+Image";

        cardImage.alt = card.name || "Pokémon Card";

        cardImage.onerror = function () {
            this.onerror = null;
            this.src = "https://placehold.co/300x420?text=No+Image";
        };

        // Card information
        document.getElementById("card-set").textContent =
            card.set_name ?? "N/A";

        document.getElementById("card-number").textContent =
            card.number ?? "N/A";

        document.getElementById("card-rarity").textContent =
            card.rarity ?? "N/A";

        document.getElementById("card-printing").textContent =
            card.printing_type ?? "N/A";

        // Format prices
        function formatPrice(price) {
            if (price === null || price === undefined || price === "") {
                return "N/A";
            }

            const amount = Number(price);

            return Number.isFinite(amount)
                ? `$${amount.toFixed(2)}`
                : "N/A";
        }

        // Market prices
        document.getElementById("market-price").textContent =
            formatPrice(card.market_price);

        document.getElementById("lowest-price").textContent =
            formatPrice(card.lowest_price);

        document.getElementById("median-price").textContent =
            formatPrice(card.median_price);

    } catch (error) {
        console.error("Unable to load card details:", error);

        document.getElementById("card-name").textContent =
            "Unable to load Pokémon card.";
    }
}