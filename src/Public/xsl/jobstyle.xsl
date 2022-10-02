<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:template match="/">
        <html>
            <head>
                <style>
                    pre {
                        white-space: pre-wrap;
                    }
                    span {
                        color: grey;
                    }
                    .file {
                        color: orange;
                    }
                    .exe {
                        color: blue;
                    }
                </style>
            </head>
            <body spellcheck="false">
                <h1>CRS Tasks</h1>
                <xsl:for-each select="job/tasks/task">
                    <h3>
                        <xsl:value-of select="@type"/>
                    </h3>

                    <pre contenteditable="true">
                        <xsl:for-each select="option">
                            <xsl:variable name="value" select="text()"/>
                            <xsl:variable name="type" select="@filetype"/>

                            <!-- Add linebreaks for some options, but only when a new parameter starts -->
                            <xsl:if test="starts-with($value, '-')">
                                <xsl:if test="starts-with($value, '-map') or string-length($value) &gt; 7">
                                    <xsl:text>\&#xa;&#x20;&#x20;</xsl:text>
                                </xsl:if>
                            </xsl:if>

                            <!-- Quote & format the value if required -->
                            <xsl:choose>
                                <xsl:when test="contains($value, ';')">
                                    <!-- xsl:variable name="x" select="replace($value, ' ; ', ' ;&#xa; ')"/ -->
                                    <span class="filter"><xsl:value-of select="concat('&quot;', $value, '&quot;')"/></span>
                                </xsl:when>
                                <xsl:when test="contains($value, ' ') and not(@quoted = 'no')">
                                    <span><xsl:value-of select="concat('&quot;', $value, '&quot;')"/></span>
                                </xsl:when>
                                <xsl:when test="$type">
                                    <span class="file">
                                        <xsl:attribute name="class"><xsl:value-of select="concat('file ', @filetype)" /></xsl:attribute>
                                        <xsl:value-of select="$value"/>
                                    </span>
                                </xsl:when>
                                <xsl:otherwise>
                                    <xsl:value-of select="$value"/>
                                </xsl:otherwise>
                            </xsl:choose>
                            <xsl:text>&#x20;</xsl:text>
                        </xsl:for-each>
                    </pre>
                </xsl:for-each>
            </body>
        </html>
    </xsl:template>
</xsl:stylesheet>
