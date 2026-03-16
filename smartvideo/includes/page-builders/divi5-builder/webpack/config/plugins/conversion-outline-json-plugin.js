const jscodeshift = require('jscodeshift');
const fs = require('fs');
const fsp = fs.promises;
const path = require('path');
const glob = require('glob');
const extractStaticProperties = require('./extract-static-properties');

/**
 * Webpack plugin that generates conversion-outline.json from conversion-outline.ts.
 */
class ConversionOutlineJsonPlugin {
	apply(compiler) {
		compiler.hooks.beforeCompile.tapAsync(
			'ConversionOutlineJsonPlugin',
			(params, callback) => {
				const searchPattern = 'src/components/**/conversion-outline.ts';

				try {
					const files = glob.sync(searchPattern);

					Promise.all(
						files.map(async (fullFilePath) => {
							try {
								const source = await fsp.readFile(
									fullFilePath,
									'utf8'
								);
								const root =
									jscodeshift.withParser('ts')(source);

								const outlineCollection = root
									.find(jscodeshift.VariableDeclarator)
									.filter(
										(astPath) =>
											astPath.value.id.name ===
											'conversionOutline'
									);

								if (0 === outlineCollection.size()) {
									return;
								}

								const conversionOutlineProperties =
									extractStaticProperties(
										outlineCollection.get().node.init
											.properties
									);

								const conversionOutlineJson = {
									_comment:
										'!!! THIS IS AN AUTOMATICALLY GENERATED FILE - DO NOT EDIT !!!',
									...conversionOutlineProperties,
								};

								const jsonContent = JSON.stringify(
									conversionOutlineJson,
									null,
									2
								);
								const outputPath = path.join(
									path.dirname(fullFilePath),
									'conversion-outline.json'
								);

								await fsp.writeFile(outputPath, jsonContent);
							} catch (fsError) {
								throw fsError;
							}
						})
					)
						.then(() => callback())
						.catch((error) => callback(error));
				} catch (error) {
					callback(error);
				}
			}
		);
	}
}

module.exports = ConversionOutlineJsonPlugin;
