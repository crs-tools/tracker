<?php $this->layout(false); ?>
<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd">
	<channel>
	<title><?php echo $project['title'] . ' (' . $profile['name'] . ')'; ?></title>
	<link><?php echo $projectProperties['Export.Mirror'] . $profile['mirror_folder'] . '/feed.xml'; ?></link>
	<atom:link href="<?php echo $projectProperties['Export.Mirror'] . $profile['mirror_folder'] . '/feed.xml'; ?>" rel="self" type="application/rss+xml" />
	<description></description>
<?php foreach ($tickets as $ticket): ?>
<?php if (isset($encodings[$ticket['fahrplan_id']])): ?>
		<item>
			<title><?php echo Filter::specialChars($ticket['title']); ?></title>
<?php if(!empty($ticket['fahrplan_subtitle'])): ?>
			<itunes:subtitle><?php echo Filter::forXML($ticket['fahrplan_subtitle']); ?></itunes:subtitle>
<?php endif; ?>
			<enclosure url="<?php echo $projectProperties['Export.Mirror'] . $profile['mirror_folder'] . '/' . $this->Properties->getFilename(array('Fahrplan.ID' => $ticket['fahrplan_id'], 'Record.Language' => $ticket['record_language'], 'Fahrplan.Slug' => $ticket['fahrplan_slug'])) . '.' . $profile['extension']; ?>" length="0" type="<?php echo MimeType::getByExtension($profile['extension']) ?>" />
			<link><?php echo sprintf($projectProperties['Fahrplan.URLScheme'], $ticket['fahrplan_id']); ?></link>
<?php if (!empty($ticket['fahrplan_abstract'])): ?>
			<description><![CDATA[<?php echo $ticket['fahrplan_abstract']; ?>]]></description>
<?php endif; ?>
			<guid isPermaLink="false"><?php echo Hash::md5($project['slug'] . $ticket['fahrplan_id'] . $profile['slug']) ?></guid>
			<pubDate><?php echo Date::fromString($encodings[$ticket['fahrplan_id']][0]['modified'], null, 'r'); ?></pubDate>
		</item>
<?php endif; ?>
<?php endforeach; ?>
	</channel>
</rss>
