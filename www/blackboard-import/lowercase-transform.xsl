<?xml version="1.0" encoding="UTF-8"?>
<!-- via http://stackoverflow.com/a/9268269 and http://www.oxygenxml.com/archives/xsl-list/200905/msg00313.html -->
<!-- we have to use XSL v1.0 because PHP's implementation of XSLT only supports v1.0. -->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

  <xsl:template match="*">
    <xsl:element name="{translate(name(), 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')}" namespace="{namespace-uri()}">
      <xsl:apply-templates select="@* | node()"/>
    </xsl:element>
  </xsl:template>

  <xsl:template match="@*">
    <xsl:attribute name="{translate(name(), 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')}" namespace="{namespace-uri()}">
      <xsl:value-of select="."/>
    </xsl:attribute>
  </xsl:template>

</xsl:stylesheet>
