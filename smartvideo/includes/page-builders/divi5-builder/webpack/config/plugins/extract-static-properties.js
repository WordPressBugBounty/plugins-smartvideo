const { reduce, map, isUndefined, filter } = require('lodash');

/**
 * Recursively extract static properties from an AST array.
 * @param properties
 */
module.exports = function extractStaticProperties(properties) {
	function firstDefined(values) {
		return values.find((val) => val !== undefined);
	}

	function extractLiteralValue(element) {
		const validLiteralTypes = [
			'StringLiteral',
			'NumericLiteral',
			'BooleanLiteral',
			'NullLiteral',
			'Literal',
		];
		return validLiteralTypes.includes(element.type)
			? element.value
			: undefined;
	}

	function extractObjectProperties(element) {
		return 'ObjectExpression' === element.type
			? extractStaticProperties(element.properties)
			: undefined;
	}

	function extractArrayValues(element) {
		return 'ArrayExpression' === element.type
			? extractArrayElements(element.elements)
			: undefined;
	}

	function extractTranslationValue(element) {
		if ('CallExpression' === element.type && '__' === element.callee.name) {
			const firstArg = element.arguments[0];
			return firstArg && 'StringLiteral' === firstArg.type
				? firstArg.value
				: undefined;
		}
		return undefined;
	}

	function extractIdentifierValue(element) {
		return 'Identifier' === element.type ? element.name : undefined;
	}

	function extractArrayElements(elements) {
		const extractedElements = map(elements, (element) => {
			const possibleValues = [
				extractLiteralValue(element),
				extractObjectProperties(element),
				extractArrayValues(element),
				extractTranslationValue(element),
				extractIdentifierValue(element),
			];
			return firstDefined(possibleValues);
		});
		return filter(extractedElements, (val) => val !== undefined);
	}

	function extractValue(value) {
		const possibleValues = [
			extractLiteralValue(value),
			extractObjectProperties(value),
			extractArrayValues(value),
			extractTranslationValue(value),
			extractIdentifierValue(value),
		];
		return firstDefined(possibleValues);
	}

	return reduce(
		properties,
		(acc, prop) => {
			const key =
				'Identifier' === prop?.key?.type
					? prop?.key?.name
					: prop?.key?.value;

			if (isUndefined(key)) {
				return acc;
			}

			const extractedValue = extractValue(prop.value);
			return isUndefined(extractedValue)
				? acc
				: { ...acc, [key]: extractedValue };
		},
		{}
	);
};
