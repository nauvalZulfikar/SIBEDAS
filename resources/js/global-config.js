export default GlobalConfig = window.GlobalConfig;

export function addThousandSeparators(value, fractionDigits = 2) {
    if (!value && value !== 0) return null; // Handle empty or null values, but allow 0

    // Convert to string first if it's a number
    if (typeof value === "number") {
        return new Intl.NumberFormat("id-ID", {
            minimumFractionDigits: fractionDigits,
            maximumFractionDigits: fractionDigits,
        }).format(value);
    }

    // If it's already a string, process it
    if (typeof value === "string") {
        // Remove any non-numeric characters except commas and dots
        value = value.replace(/[^0-9,.]/g, "");

        // If the value contains multiple dots, assume dots are thousand separators
        if ((value.match(/\./g) || []).length > 1) {
            value = value.replace(/\./g, "");
        }

        // Convert to a proper decimal number
        let number = parseFloat(value.replace(",", "."));

        if (isNaN(number)) return null; // Return null if conversion fails

        // Format the number with Indonesian format (dot for thousands, comma for decimal)
        return new Intl.NumberFormat("id-ID", {
            minimumFractionDigits: fractionDigits,
            maximumFractionDigits: fractionDigits,
        }).format(number);
    }

    return null; // Return null for unsupported types
}
