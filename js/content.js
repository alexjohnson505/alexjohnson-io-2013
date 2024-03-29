// "" : {
// 		title : "",
// 		description : "",
// 		tags : "",
// 		image : "",
// 	}
function postTemplate (name){
	return '<hr/><h2>{{title}}</h2><hr /><img src="images/preview/' + name + '.jpg"><p><i>{{{summary}}}</i></p><p>{{{description}}}</p><p>Tags: {{tags}}</p><p><a href="javascript:$.fancybox.close();">Close</a></p>';
}

var content =
{
	"evertrue-amp" : {
		title : "EverTrue's Admin Management Portal",
		summary : "EverTrue's Admin Management Portal is a client-facing web tool for customizing and editing app settings for EverTrue's mobile applications.",
		description : "EverTrue is a mobile platform developer, working to strengthen alumni relationships through the intersection of mobile applications, institutional knowledge, and social media such as LinkedIn. As a web development co-op at EverTrue, I worked alongside their product lead to prepare their Admin Management Portal. The Admin Management Portal is a self-service tool for clients and admins. Leveraging Backbone.js, underscore.js and a PHP/MySQL powered internal API we brought this single page javascript web app from being used internally used, to a client-facing self-service tool. I held responsibility over a variety of features, such as the landing pages, authentication aesthetics and UX, maintaining the bootstrap based style guide, investigating bug reports from client services, responsive design, and the management page for suggested updates. Working alongside and coordinating product development between iOS, Android, Backend, and Client Services provided to be a rewarding part of the job.",
		category : "web",
		tags : "Web Development, EverTrue, CSS3, HTML, Javascript, Backbone, jQuery, Bootstrap, Git",
	},
	"linkedin-grader" : {
		title : "EverTrue's LinkedIn Grader",
		summary : "EverTrue's LinkedIn Grader is an online tool for measuring a Linked In alumni group's popularity based on similar institutions.",
		description : "Expanding off of a hackathon project, I worked alongside EverTrue's head data specialist to prepare the LinkedIn Grader for a public launch. Working off of an existing set of API calls to LinkedIn's partner API, I worked to build the user experience, auth the user through LinkedIn account authentication, and publish/market the new web tool to generate more leads from potential clients.",
		category : "web",
		tags : "Web Development, EverTrue, CSS3, HTML, Javascript, jQuery, AJAX, Hackathon, Bootstrap, Git",
	},
	"eventler" : {
		title : "EverTrue's Eventler",
		summary : "EverTrue's Eventler is a event-automation and creation tool.",
		description : "Eventler was a Javascript mini-tool for automating social event creation. Born during a hackathon, my team built a proof of concept tool showing that EventBrite Events, Facebook events, and e-mail marketing can be automated through a javascript web page calling various public APIs. Working alongside the PHP specialist at EverTrue, I worked to write the javascript DOM manipulation, display options to the user, and execute API calls.",
		category : "web",
		tags : "Web Development, EverTrue, CSS3, HTML, Javascript, jQuery, Hackathon, Bootstrap",
	},
	"fitbit-challenge" : {
		title : "EverTrue's FitBit Challenge",
		summary : "EverTrue's FitBit Challenge is an online ranking and comparison tool for competative step-counting.",
		description : "EverTrue's 1,000,000 Step Challenge was a company wide fitness initiative to maintain a 10,000 steps per day average. When the FitBit Dashboard didn't provide the level of competitiveness that we desired, we took the initiative to build our own leaderboard and tracker website. The website accumulated data available through FitBit's API into a comparison of the entire team in relation to each other, complete with leaderboard, steps-to-go, average, tracking graphics and 1 vs. 1 profile comparison statistics. The site was built supporting retina and fluid layout to create a mobile-first experience.",
		category : "web",
		tags : "Web Development, EverTrue, CSS3, HTML, Javascript, jQuery, Hackathon",
	},
	"evertrue-email" : {
		title : "EverTrue E-mail Campaigns",
		summary : "EverTrue's E-mail campaigns were a series of custom templates for e-mail marketing.",
		description : "As we experimented with the success and effectiveness of email campaigns and marketing, I created a number of e-mail templates from our designer's mockups. I then ported these e-mails into hubspot and mailchimp compatible templates, where they would be used to send out mail to thousands of our users. The largest challenge was writing and testing HTML/CSS to be compatible among a wide variety of email clients. Everything from iPhone 5 and Gmail, through Outlook had to be supported",
		category : "web",
		tags : "EverTrue, CSS, HTML, email",
	},
	"texture-report" : {
		title : "Texture Report.com",
		summary : "TextureReport is a texture analysis blog devoted to highlighting interesting news and developments in the industries surrounding material testing.",
		description : "Texturetechnologies.com/blog, formally TextureReport.com, is a blog devoted to developments in the world of texture analysis testing. TextureReport.com is run by the team behind Texture Technologies, the North American reseller of the industry gold standard for testing texture analysis, the TA.XTPlus Texture Analyzer. Powered by wordpress, TextureReport.com is updated bi-monthly and highlights the most recent application studies, events and news in the Texture Testing community. Subscriptions work through Mailchimp, where subscribers can opt to recieve weekly updates generated by changes to the blog's RSS feed.",
		category : "web design",
		tags : "Web Development, Texture Technologies, Graphic Design, Web Design, CSS3, HTML, PHP, Wordpress, email",
	},
		"texture-channel" : {
		title : "Texture Channel.com",
		summary : "TextureChannel.com is a subscription service for streaming Texturetechnologies training videos.",
		description : "I have been working with Texture Technologies for a long time to produce tutorials of their instruments and software. I convinced Texture Technologies that the best method for distributing the video list to subscribed clients would be an account-based video streaming site. A single point of access for Texture Technologies' training videos would be the most convenient and enjoyable for the user, as well as  cost-effective and easy to maintain for Texture Technologies. Not only did I assist in filming, editing, and producing the video work, but I developed the website used to organize and stream the videos. The site itself pulls the video list and meta data from a MySQL database, and uses PHP Sessions and another MySQL table of user records to authenticate user. Admins can create new user accounts and set an access expiration date for subscribers. The site uses AJAX to dynamically load the content and generate the ordered menu of available content at login.",
		category : "web",
		tags : "Web Development, Texture Technologies, Web Design, Graphic Design, Video, CSS3, HTML, Javascript, jQuery, AJAX, MySQL",
	},
		"ttc-training-videos" : {
		title : "TA.XT Plus and Exponent Training Videos",
		summary : "TA.XT Plus and Exponent Training Videos are a library of training resources for learning the TA.XT2 plus instruments and Exponent software for testing.",
		description : "As part of an ongoing video training series, I have done all the filming, setup, editing, hardware purchases, and publishing of the Texture Technologies' training videos seen on texturechannel.com. These videos are part of a 50+ library of HD training videos, about the use of Texture Technologies' TA.XT2 Plus instruments and Exponent software, all of which are available to clients as a subscription service.",
		category : "web",
		tags : "Texture Technologies, Video, Premiere Pro, Tutorial",
	},
		"texturetechnologies" : {
		title : "TextureTechnologies.com",
		summary : "TextureTechnologies.com is the company website for Texture Technologies, featuring product details, attachment probes and accessories, contact information, form requests, and available application studies.",
		description : "I have worked with Texture Technologies for a few years, providing assistance with updating their website, filming training videos, and creating web solutions for content distribution. I have also collaborated to film tutorial videos for companies such as the Wheat Marketing Center, in Oregon, and to film the tutorials for their hardware and software. I constantly manage the content and design of their main website: TextureTechnologies.com",
		category : "web",
		tags : "Texture Technologies, Web Development, PHP, email, Git",
	},
		"quoth-the-raver" : {
		title : "QuoththeRaver.com",
		summary : "Quoth the Raver is a music blog covering genres such as Electro, House, Drum & Bass, Dubstep, Glitch Hop, Trance.",
		description : "I designed quoth-the-raver's first blog two years ago, and after some discussion we decided to upgrade to a wordpress format. For the design, we built a balance between tight organization and funky dance-inspired images.",
		category : "web",
		tags : "Web Development, Wordpress, PHP, Web Design",
	},
		"clintvalentine" : {
		title : "ClintValentine.com",
		summary : "ClintValentine.com is the personal journal site for Clint Valentine - explorer, adventurer, cyclist, and scientist. Clint is a Biology & Environmental Science major, whose passions include rock climbing, writing, photography and adventuring. ",
		description : "Each documented journey sits as it's own category, with Colorado recently wrapping up, and more categories and stories coming in the future. Challenges building ClintValentine.com included constraints on content updating - where the author would have limited device and internet access when traveling, as well as mobile device support regarding how does a site balance large photographs with mobile support. Clint Valentine was recently featured in an article on National Geographic's website, as well as tweeted about by President Aoun, the president of Northeastern.",
		tags : "Web Development, Wordpress, PHP, Web Design",
		category : "web",
	},
		"firebrand-innovations" : {
		title : "FirebrandInnovations.com",
		description : "FirebrandInnovations.com is a company flier site for Firebrand Innovations  - a growing intellectual property development startup. Future progress for Firebrand Innovations includes developments on their video conferencing technologies. While the website is currently a skeleton site and a placeholder, we're waiting on some large developments to finish unfolding before going public.",
		category : "web",
		tags : "Web Development, Wordpress, Web Design, Bootstrap, Javascript",
	},
		"branding" : {
		title : "Branding Design",
		description : "",
		category : "graphic",
		tags : "Graphic Design, Illustrator, Photoshop",
		gallery : "[Gallery]",
	},
		"salem-state" : {
		title : "Salem State University Web Design",
		description : "During Summer 2010 through Spring 2011 I was hired by the IT department of Salem State University to develop and work on their web team. My focus was to propose designs and improvement to a number of tools used by the faculty and students. I used CSS, jQuery, and PHP to create mock-up designs, with the goal of emulating and user-testing improvements to internal web tools. After designing a number of websites and layouts for employee and professor tools, my work extended into design proposals for the student and teacher school portal.",
		category : "web",
		tags : "Web Design, CSS3, Photoshop, HTML",
	},
		"fmap" : {
		title : "Iterating over an FMap | FMap <K,V> Red/Black Tree",
		description : 'A programming challenge: Design the data class and Iterator that handles iterating over a parameterized FMap. FMap is an immutable abstract data type with parameterized values that represent keys of type K to values of type V. In addition, design FMapIterator which implements Iterator and iterates over the keys of a given FMap. To download the source file (.java) right mouse click, and save the following link: <a href="download/FMap.java">Download Fmap and Iterator source file</a>',
		category : "programming",
		tags : "Java",
	},
 		"tetris" : {
		title : "Tetris in Scheme",
		description : 'Here is a fun little assignment from freshman year. Programming Tetris in Racket using DrRacket (dialect of scheme). A partner and I wrote this code as one of the final projects in an Introduction to Programming course. It is basic tetris . It has the blocks, row removal, rotation, templates, and a plethora of tests. Right & Left to move the block, A & S to rotate. To download the source file (.rkt) right mouse click, and save the following link: <a href="download/tetris_in_drracket_scheme.rkt">Download Tetris in DrRacket</a>',
		category : "programming",
		tags : "Scheme",
	},
		"fire" : {
		title : "Fire Performance Photography",
		description : "",
		category : "photography",
		tags : "Photography, Photoshop",
		gallery : "[gallery]",
	},
		"landscape" : {
		title : "Landscape Photography",
		description : "",
		category : "photography",
		tags : "Photography",
		image : "[gallery]",
	},
		"macro" : {
		title : "Macro Photography",
		description : "",
		category : "photography",
		tags : "Photography",
		gallery : '',
	},
		"quadcopter" : {
		title : "Quadcopter Project",
		summary: "Our goal was to develop an autonomous Quadcopter, capable of navigating in 3-dimensional space, ultimately leading to opportunities in mapping and aerial photography.",
		description : "A quadcopter, also known as a quadrotor helicopter, is an instrument lifted and propelled by four rotors. Compared to traditional 2-propellor helipcopter solutions, quadcopters have become increasingly popular due to their stability and maneuverability. Nate Lilienthal, Dan Calacci, and I worked together to research parts, then purchase and assemble the structure. In addition, we experimented with new math concepts such as kalman filters and quaternions, and played with ruby and C++ on Arduino. The project is still a work in progress;  though capable of flight, our 'in-development' stabilization algorithm needs more work before we attempt higher altitudes ",
		category : "programming",
		tags : "Ruby, C++, Latex",
	},
		"quadcopter-docs" : {
		title : "Quadcopter Documentation",
		description : "While developing our Quadcopter, my friend Nathan and I were surprised by the lack of official documentation on the assembly of quadcopters. This necessitated the reading of the technical documentation for various parts, in addition to trial and error. We made a goal, that not only would we build a quadcopter from 'scratch', but that we would properly document our experience - in everything from our hardware and our code, to our successes and failures. The goal was to contribute to the online community, and save future quadcopter developers from expending the amount of time and effort we experienced. While not finalized yet, the quadcopter docs are currently running Ruby and Sinatra to parse HTML and Latex from markdown files and render as a website. These technologies were chosen so that the documentation collaboration could happen through markdown files on GitHub.",
		category : "web",
		tags : "Ruby, Sinatra, Git, Latex",
	},
		"b2q-associates" : {
		title : "B2Q Associates.com",
		description : "B2Q Associates is a North Andover company specializing in a variety of engineering consulting services. Their licensed engineers, managers and designers provide services such as energy efficiency analysis, Energy Star certification, HVAC system design and building retro-commissioning. A few years ago, I worked to give their website a face-lift. They wanted a modern, clean design that was easy to maintain without knowledge of HTML, PHP or other languages. Using Wordpress, I built a site that included purchasing stock photography, a featured-item slider for their services, a scrolling client logo list, and contact form.",
		category : "programming",
		tags : "Wordpress",
	},
		"wheat-marketing-center" : {
		title : "Wheat Marketing Center",
		description : "Through Texture Technologies Corp, I was introduced to Dr. Gary Hou at the Wheat Marketing Center in Oregon. I worked to film, edit and produce an instructional video of measuring texture firmness. The video was distributed through wheat suppliers and food scientists on the West Coast in order to standardize material testing procedures.",
		category : "photography",
		tags : "Video, Training, Premiere Pro",
	}
}