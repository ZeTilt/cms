<?php

namespace App\Command;

use App\Entity\Page;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'zetilt:demo:create-pages',
    description: 'Create demo pages and blog posts',
)]
class CreateDemoPagesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Creating Demo Pages and Blog Posts');

        // Get admin user
        $admin = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'admin@zetilt.cms']);

        if (!$admin) {
            $io->error('Admin user not found. Please run zetilt:cms:init first.');
            return Command::FAILURE;
        }

        // Create demo pages
        $this->createDemoPages($admin, $io);
        
        // Create demo blog posts
        $this->createDemoBlogPosts($admin, $io);

        $this->entityManager->flush();

        $io->success('Demo pages and blog posts created successfully!');
        $io->note('Visit /admin/pages to manage them');

        return Command::SUCCESS;
    }

    private function createDemoPages(User $admin, SymfonyStyle $io): void
    {
        $io->section('Creating Demo Pages');

        $pages = [
            [
                'title' => 'About',
                'content' => '<h2>About This Site</h2>
                <p>Welcome to our professional portfolio and photography website. We specialize in creating beautiful, memorable experiences through our various services.</p>
                
                <h3>Our Mission</h3>
                <p>To capture and preserve life\'s most precious moments with artistic vision and technical excellence.</p>
                
                <h3>What We Offer</h3>
                <ul>
                    <li><strong>Photography Services:</strong> Professional photo shoots for individuals, families, and events</li>
                    <li><strong>Portfolio Development:</strong> Custom portfolio creation for artists and professionals</li>
                    <li><strong>Event Coverage:</strong> Comprehensive documentation of your special occasions</li>
                </ul>',
                'excerpt' => 'Learn more about our mission and the services we provide.',
                'type' => 'page',
            ],
            [
                'title' => 'Services',
                'content' => '<h2>Our Professional Services</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 my-8">
                    <div class="bg-gray-50 p-6 rounded-lg">
                        <h3>📸 Photography Sessions</h3>
                        <p>Professional photo shoots tailored to your needs:</p>
                        <ul>
                            <li>Portrait photography</li>
                            <li>Family sessions</li>
                            <li>Corporate headshots</li>
                            <li>Product photography</li>
                        </ul>
                    </div>
                    
                    <div class="bg-gray-50 p-6 rounded-lg">
                        <h3>🎨 Portfolio Development</h3>
                        <p>Complete portfolio solutions:</p>
                        <ul>
                            <li>Custom website design</li>
                            <li>Print portfolio creation</li>
                            <li>Digital gallery management</li>
                            <li>Brand development</li>
                        </ul>
                    </div>
                </div>
                
                <p class="text-center mt-8">
                    <strong>Ready to get started?</strong><br>
                    <a href="/contact" class="bg-blue-600 text-white px-6 py-3 rounded-lg inline-block mt-2">Contact Us Today</a>
                </p>',
                'excerpt' => 'Discover our range of professional photography and portfolio services.',
                'type' => 'page',
            ],
            [
                'title' => 'Contact',
                'content' => '<h2>Get In Touch</h2>
                <p>We\'d love to hear from you! Whether you\'re interested in a photography session, need help with your portfolio, or just have questions, don\'t hesitate to reach out.</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 my-8">
                    <div>
                        <h3>Contact Information</h3>
                        <div class="space-y-3">
                            <p><strong>📧 Email:</strong> <a href="mailto:contact@example.com">contact@example.com</a></p>
                            <p><strong>📱 Phone:</strong> <a href="tel:+1234567890">+1 (234) 567-890</a></p>
                            <p><strong>🌍 Location:</strong> Available worldwide</p>
                            <p><strong>🕒 Hours:</strong> Monday - Friday, 9AM - 6PM</p>
                        </div>
                    </div>
                    
                    <div>
                        <h3>Follow Us</h3>
                        <div class="space-y-2">
                            <p><strong>Instagram:</strong> <a href="https://instagram.com/example" target="_blank">@example</a></p>
                            <p><strong>LinkedIn:</strong> <a href="https://linkedin.com/company/example" target="_blank">Company Profile</a></p>
                            <p><strong>Facebook:</strong> <a href="https://facebook.com/example" target="_blank">Example Page</a></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-blue-50 p-6 rounded-lg mt-8">
                    <h3>💼 Ready to Work Together?</h3>
                    <p>We typically respond to inquiries within 24 hours. Please include details about your project, timeline, and any specific requirements.</p>
                </div>',
                'excerpt' => 'Contact us for photography services, portfolio development, or any questions.',
                'type' => 'page',
            ]
        ];

        foreach ($pages as $pageData) {
            $page = new Page();
            $page->setTitle($pageData['title'])
                 ->setContent($pageData['content'])
                 ->setExcerpt($pageData['excerpt'])
                 ->setType($pageData['type'])
                 ->setStatus('published')
                 ->setAuthor($admin);
                 
            // The generateSlug is called automatically by setTitle, but let's make sure
            if (!$page->getSlug()) {
                $page->generateSlug();
            }
                 
            $page->publish();

            $this->entityManager->persist($page);
            $io->writeln("✓ Created page: {$pageData['title']}");
        }
    }

    private function createDemoBlogPosts(User $admin, SymfonyStyle $io): void
    {
        $io->section('Creating Demo Blog Posts');

        $posts = [
            [
                'title' => 'Welcome to Our Blog',
                'content' => '<p>Welcome to our blog! Here we\'ll share insights about photography, portfolio development, and creative processes.</p>
                
                <h2>What to Expect</h2>
                <p>Our blog will cover a variety of topics:</p>
                
                <ul>
                    <li><strong>Photography Tips:</strong> Technical and creative advice for better photos</li>
                    <li><strong>Behind the Scenes:</strong> Stories from our photo shoots and projects</li>
                    <li><strong>Portfolio Insights:</strong> How to create compelling visual narratives</li>
                    <li><strong>Industry Updates:</strong> Latest trends and technologies in photography</li>
                </ul>
                
                <p>We\'re excited to share our knowledge and connect with fellow creatives. Stay tuned for regular updates!</p>',
                'excerpt' => 'Welcome to our blog where we share photography insights and creative inspiration.',
                'tags' => ['welcome', 'photography', 'blog'],
                'published_at' => new \DateTimeImmutable('-5 days'),
            ],
            [
                'title' => 'The Art of Portrait Photography',
                'content' => '<p>Portrait photography is more than just capturing someone\'s appearance – it\'s about revealing personality, emotion, and character in a single frame.</p>
                
                <h2>Key Elements of Great Portraits</h2>
                
                <h3>1. Connection with the Subject</h3>
                <p>The best portraits happen when there\'s a genuine connection between photographer and subject. Take time to chat, get comfortable, and understand what makes your subject unique.</p>
                
                <h3>2. Lighting is Everything</h3>
                <p>Whether you\'re using natural window light or studio strobes, understanding how light shapes the face is crucial. Consider:</p>
                <ul>
                    <li>Direction of light (front, side, back)</li>
                    <li>Quality of light (hard vs soft)</li>
                    <li>Color temperature</li>
                </ul>
                
                <h3>3. Focus on the Eyes</h3>
                <p>Eyes are the window to the soul, and they should almost always be the sharpest part of your portrait. Use single-point autofocus for precision.</p>
                
                <h2>Technical Tips</h2>
                <ul>
                    <li><strong>Aperture:</strong> Use f/1.8-f/4 for shallow depth of field</li>
                    <li><strong>Focal Length:</strong> 85mm-135mm lenses are ideal for portraits</li>
                    <li><strong>Shutter Speed:</strong> At least 1/125s to avoid camera shake</li>
                </ul>
                
                <p>Remember, technical perfection means nothing without emotional impact. Focus on capturing authentic moments and expressions.</p>',
                'excerpt' => 'Explore the essential techniques and artistic principles behind compelling portrait photography.',
                'tags' => ['portrait', 'photography', 'tutorial', 'lighting'],
                'published_at' => new \DateTimeImmutable('-2 days'),
            ],
            [
                'title' => 'Building Your Creative Portfolio',
                'content' => '<p>A strong portfolio is essential for any creative professional. It\'s your visual resume, your first impression, and often your best marketing tool.</p>
                
                <h2>Portfolio Fundamentals</h2>
                
                <h3>Quality Over Quantity</h3>
                <p>It\'s better to show 15-20 exceptional pieces than 50 mediocre ones. Be ruthlessly selective – every image should serve a purpose.</p>
                
                <h3>Tell a Story</h3>
                <p>Your portfolio should have a narrative flow. Consider:</p>
                <ul>
                    <li>What\'s your unique style or perspective?</li>
                    <li>What problems do you solve for clients?</li>
                    <li>How do your pieces work together as a cohesive body of work?</li>
                </ul>
                
                <h3>Know Your Audience</h3>
                <p>Tailor your portfolio to your target clients. A wedding photographer\'s portfolio should look different from a commercial photographer\'s.</p>
                
                <h2>Digital vs Physical Portfolios</h2>
                
                <h3>Digital Portfolios</h3>
                <ul>
                    <li><strong>Pros:</strong> Easy to update, shareable, searchable</li>
                    <li><strong>Cons:</strong> Screen quality varies, requires device to view</li>
                    <li><strong>Best for:</strong> Initial client contact, social media sharing</li>
                </ul>
                
                <h3>Physical Portfolios</h3>
                <ul>
                    <li><strong>Pros:</strong> Tangible experience, controlled viewing environment</li>
                    <li><strong>Cons:</strong> Expensive to produce, harder to update</li>
                    <li><strong>Best for:</strong> Client meetings, gallery submissions</li>
                </ul>
                
                <h2>Keep It Fresh</h2>
                <p>Update your portfolio regularly. Remove older work that no longer represents your current skill level, and add new pieces that showcase your growth.</p>',
                'excerpt' => 'Learn how to create a compelling creative portfolio that showcases your best work and attracts ideal clients.',
                'tags' => ['portfolio', 'creative', 'business', 'marketing'],
                'published_at' => new \DateTimeImmutable('yesterday'),
            ]
        ];

        foreach ($posts as $postData) {
            $page = new Page();
            $page->setTitle($postData['title'])
                 ->setContent($postData['content'])
                 ->setExcerpt($postData['excerpt'])
                 ->setTags($postData['tags'])
                 ->setType('blog')
                 ->setStatus('published')
                 ->setAuthor($admin)
                 ->setPublishedAt($postData['published_at']);
                 
            // The generateSlug is called automatically by setTitle, but let's make sure
            if (!$page->getSlug()) {
                $page->generateSlug();
            }
                 
            $page->publish();

            $this->entityManager->persist($page);
            $io->writeln("✓ Created blog post: {$postData['title']}");
        }
    }
}