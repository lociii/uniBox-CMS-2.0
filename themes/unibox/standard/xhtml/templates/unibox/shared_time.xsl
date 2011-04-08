<?xml version="1.0" encoding="UTF-8"?>

<!--
#################################################################################################
###
### uniBox 2.0 (winterspring)
### (c) Media Soma GbR
###
#################################################################################################
-->

<xsl:stylesheet version="1.0" xmlns="http://www.w3.org/1999/xhtml" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:ubc="http://www.media-soma.de/ubc">
	<xsl:template name="ub_time">
		<xsl:param name="show" />
		<xsl:param name="show_seconds" />
		<xsl:param name="show_month_as_word" />

		<xsl:choose>
<!-- BEGIN TIME -->
			<xsl:when test="$show = 'time'">
				<xsl:choose>
<!-- ////////// BEGIN german ////////// -->
					<xsl:when test="/root/unibox/user_locale = 'de_DE'">
						<xsl:value-of select="ub_time/time/hour" />:<xsl:value-of select="ub_time/time/minute" />
						<xsl:if test="$show_seconds">:<xsl:value-of select="ub_time/time/second" /></xsl:if>
					</xsl:when>
<!-- ////////// END german ////////// -->
<!-- ////////// BEGIN default ////////// -->
					<xsl:otherwise>
						<xsl:value-of select="ub_time/time/hour" />:<xsl:value-of select="ub_time/time/minute" />
						<xsl:if test="$show_seconds">:<xsl:value-of select="ub_time/time/second" /></xsl:if>
					</xsl:otherwise>
<!-- ////////// END default ////////// -->
				</xsl:choose>
			</xsl:when>
<!--  END TIME  -->
		</xsl:choose>
	</xsl:template>

	<xsl:template name="ub_time_seconds">
		<xsl:choose>
			<xsl:when test="/root/unibox/user_locale = 'de_DE'">
				<xsl:value-of select="ub_time/time/hour" />:<xsl:value-of select="ub_time/time/minute" />:<xsl:value-of select="ub_time/time/second" />
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="ub_time/time/hour" />:<xsl:value-of select="ub_time/time/minute" />:<xsl:value-of select="ub_time/time/second" />
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template name="ub_date">
		<xsl:choose>
			<xsl:when test="/root/unibox/user_locale = 'de_DE'">
				<xsl:value-of select="ub_time/date/day" />.<xsl:value-of select="ub_time/date/month" />.<xsl:value-of select="ub_time/date/year" />
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="ub_time/date/year" />-<xsl:value-of select="ub_time/date/month" />-<xsl:value-of select="ub_time/date/day" />
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template name="ub_date_mon">
		<xsl:choose>
			<xsl:when test="/root/unibox/user_locale = 'de_DE'">
				<xsl:value-of select="ub_time/date/day" />. <xsl:value-of select="ub_time/date/month_word" />&#160;<xsl:value-of select="ub_time/date/year" />
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="ub_time/date/month_word" />&#160;<xsl:value-of select="ub_time/date/day" />, <xsl:value-of select="ub_time/date/year" />
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template name="ub_datetime">
		<xsl:choose>
			<xsl:when test="/root/unibox/user_locale = 'de_DE'">
				<xsl:call-template name="ub_date" />&#160;<xsl:call-template name="ub_time" />
			</xsl:when>
			<xsl:otherwise>
				<xsl:call-template name="ub_date" />&#160;<xsl:call-template name="ub_time" />
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template name="ub_datetime_mon">
		<xsl:choose>
			<xsl:when test="/root/unibox/user_locale = 'de_DE'">
				<xsl:call-template name="ub_date_mon" />&#160;<xsl:call-template name="ub_time" />
			</xsl:when>
			<xsl:otherwise>
				<xsl:call-template name="ub_date_mon" />&#160;<xsl:call-template name="ub_time" />
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template name="ub_datetime_word">
		<xsl:choose>
			<xsl:when test="/root/unibox/user_locale = 'de_DE'">
				<xsl:choose>
					<xsl:when test="ub_time/date/word">
						<xsl:value-of select="ub_time/date/word" />,
					</xsl:when>
					<xsl:otherwise>
						<xsl:call-template name="ub_date" />
					</xsl:otherwise>
				</xsl:choose>&#160;<xsl:call-template name="ub_time" />
			</xsl:when>
			<xsl:otherwise>
				<xsl:choose>
					<xsl:when test="ub_time/date/word">
						<xsl:value-of select="ub_time/date/word" />,
					</xsl:when>
					<xsl:otherwise>
						<xsl:call-template name="ub_date" />
					</xsl:otherwise>
				</xsl:choose>&#160;<xsl:call-template name="ub_time" />
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template name="ub_datetime_seconds">
		<xsl:choose>
			<xsl:when test="/root/unibox/user_locale = 'de_DE'">
				<xsl:call-template name="ub_date" />&#160;<xsl:call-template name="ub_time_seconds" />
			</xsl:when>
			<xsl:otherwise>
				<xsl:call-template name="ub_date" />&#160;<xsl:call-template name="ub_time_seconds" />
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template name="ub_datetime_word_seconds">
		<xsl:choose>
			<xsl:when test="/root/unibox/user_locale = 'de_DE'">
				<xsl:choose>
					<xsl:when test="ub_time/date/word">
						<xsl:value-of select="ub_time/date/word" />,
					</xsl:when>
					<xsl:otherwise>
						<xsl:call-template name="ub_date" />
					</xsl:otherwise>
				</xsl:choose>&#160;<xsl:call-template name="ub_time_seconds" />
			</xsl:when>
			<xsl:otherwise>
				<xsl:choose>
					<xsl:when test="ub_time/date/word">
						<xsl:value-of select="ub_time/date/word" />,
					</xsl:when>
					<xsl:otherwise>
						<xsl:call-template name="ub_date" />
					</xsl:otherwise>
				</xsl:choose>&#160;<xsl:call-template name="ub_time_seconds" />
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
</xsl:stylesheet>