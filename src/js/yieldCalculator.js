function isValidIngredientSyntax(ingredient) {
    /*
        The ingredientSyntaxRegExp checks whether the ingredient string starts with a number, 
        possibly followed by a fractional part or a fraction. Then there should be a space
        and then any sequence of characters.
    */
    const ingredientSyntaxRegExp = /^(?:\d+(?:\.\d+)?(?:\/\d+)?)\s?.*$/

    /*
        The ingredientFractionRegExp is used to identify fractions in the string.
        This is used to exclude strings that contain fractions from being valid.
    */
    const ingredientFractionRegExp = /\b\d+\/\d+\b/g

    /*
        The ingredientMultipleSeperatorsRegExp is used to check whether the string contains
        more than one separators (.,) after a number. This is used to exclude strings that 
        contain more than one separator from being valid.
    */
    const ingredientMultipleSeperatorsRegExp = /^-?\d+(?:[.,]\d+){2,}.*/

    return (
        ingredientSyntaxRegExp.test(ingredient) &&
        !ingredientFractionRegExp.test(ingredient) &&
        !ingredientMultipleSeperatorsRegExp.test(ingredient)
    )
}

function isIngredientsArrayValid(ingredients) {
    return ingredients.every(isValidIngredientSyntax)
}

function recalculateIngredients(ingredients, currentYield, originalYield) {
    return ingredients.map((ingredient, index) => {
        const fractionRegExp = /(\d+\s)?(\d+)\/(\d+)/
        const matches = ingredient.match(fractionRegExp)

        if (matches) {
            const [
                fullMatch,
                wholeNumberPartRaw,
                numeratorRaw,
                denominatorRaw,
            ] = matches
            const wholeNumberPart = wholeNumberPartRaw
                ? parseInt(wholeNumberPartRaw, 10)
                : 0
            const numerator = parseInt(numeratorRaw, 10)
            const denominator = parseInt(denominatorRaw, 10)

            const decimalAmount = wholeNumberPart + numerator / denominator
            let newAmount = (decimalAmount / originalYield) * currentYield
            newAmount = newAmount.toFixed(2).replace(/[.]00$/, "")

            const newIngredient = ingredient.replace(fullMatch, newAmount)
            return newIngredient
        }

        if (isValidIngredientSyntax(ingredient)) {
            const possibleUnit = ingredient
                .split(" ")[0]
                .replace(/[^a-zA-Z]/g, "")
            const amount = parseFloat(
                ingredients[index].split(" ")[0].replace(",", "."),
            )
            const unitAndIngredient = ingredient.split(" ").slice(1).join(" ")

            let newAmount = (amount / originalYield) * currentYield
            newAmount = newAmount.toFixed(2).replace(/[.]00$/, "")

            return `${newAmount}${possibleUnit} ${unitAndIngredient}`
        }

        return ingredient
    })
}

export default {
    isValidIngredientSyntax,
    isIngredientsArrayValid,
    recalculateIngredients,
}
