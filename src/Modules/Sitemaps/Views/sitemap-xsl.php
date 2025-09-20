<?php
/**
 * Minimal XSL stylesheet for human-friendly display in browsers.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
header_remove( 'Content-Type' ); // set by caller
echo <<<XSL
<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:html="http://www.w3.org/TR/REC-html40"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
	<xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes"/>
	<xsl:template match="/">
		<html>
			<head>
				<title>Sitemap — Theme SEO Core</title>
				<meta charset="utf-8"/>
				<style>
					body{font:14px/1.5 system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif; padding:24px; color:#111}
					table{border-collapse:collapse; width:100%}
					th,td{border-bottom:1px solid #e5e7eb; text-align:left; padding:8px}
					th{font-weight:600; font-size:12px; text-transform:uppercase; color:#374151}
					tr:hover td{background:#f9fafb}
					.small{color:#6b7280}
				</style>
			</head>
			<body>
				<h1>Sitemap</h1>

				<xsl:choose>
					<xsl:when test="sitemapindex">
						<p class="small">Sitemap index</p>
						<table>
							<thead><tr><th>Location</th><th>Last Modified</th></tr></thead>
							<tbody>
								<xsl:for-each select="sitemapindex/sitemap">
									<tr>
										<td><a><xsl:attribute name="href"><xsl:value-of select="loc"/></xsl:attribute><xsl:value-of select="loc"/></a></td>
										<td><xsl:value-of select="lastmod"/></td>
									</tr>
								</xsl:for-each>
							</tbody>
						</table>
					</xsl:when>
					<xsl:otherwise>
						<p class="small">URL set</p>
						<table>
							<thead><tr><th>URL</th><th>Last Modified</th><th>Images</th></tr></thead>
							<tbody>
								<xsl:for-each select="urlset/url">
									<tr>
										<td><a><xsl:attribute name="href"><xsl:value-of select="loc"/></xsl:attribute><xsl:value-of select="loc"/></a></td>
										<td><xsl:value-of select="lastmod"/></td>
										<td>
											<xsl:for-each select="image:image">
												<div class="small"><xsl:value-of select="image:loc"/></div>
											</xsl:for-each>
										</td>
									</tr>
								</xsl:for-each>
							</tbody>
						</table>
					</xsl:otherwise>
				</xsl:choose>
			</body>
		</html>
	</xsl:template>
</xsl:stylesheet>
XSL;

