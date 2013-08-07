# Canvas res00000.dat Types

- <CONTENT> could be a content item, a course link or an external link, and might have attachments.
- <GRADEBOOK> contains the assignment groups, grading periods, grading schemes, summary column formulae and assignments (called <OUTCOMES>).
- <ANNOUNCEMENT> is just what you think it is. (That's right: a baby octopus.)
- <QUESTESTINTEROP> is an online test (or survey, I guess). (And <ASSESSMENTCREATIONSETTINGS> seem to be the Bb-specific settings for the test each time it is assigned.)
- <LINK> is a course link -- it connects a <referrer> and a <referredto> (neither of which, of course, actually reference the link by ID or name). <LINK>s only seem to show up in the Resources section of the manifest. You know to look for a link because a <CONTENT> item includes <CONTENTHANDLER value="resource/x-bb-courselink"/>. Oy.
- <FORUM> is a discussion board, with all of the discussion threads included
- <GROUPS> are student groups and their settings for the class.	
- <CONTENTHANDLERS>, <NAVIGATIONAPPLICATIONS>, <NAVIGATIONSETTINGS>, <NOTIFICATIONRULES> all seem to have to do with settings within the class.