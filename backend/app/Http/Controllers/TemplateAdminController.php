<?php

namespace App\Http\Controllers;

use App\Models\MessageTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TemplateAdminController extends Controller
{
    /**
     * List all templates
     * GET /api/admin/templates
     */
    public function index(Request $request): JsonResponse
    {
        $query = MessageTemplate::query();

        if ($request->has('channel')) {
            $query->where('channel', $request->channel);
        }

        if ($request->has('language')) {
            $query->where('language', $request->language);
        }

        if ($request->has('template_type')) {
            $query->where('template_type', $request->template_type);
        }

        if ($request->has('active_only')) {
            $query->where('is_active', true);
        }

        $templates = $query->orderBy('sort_order')
            ->orderBy('language')
            ->orderBy('template_type')
            ->orderBy('name')
            ->get();

        return response()->json([
            'templates' => $templates,
            'languages' => MessageTemplate::LANGUAGES,
            'channels' => MessageTemplate::CHANNELS,
            'variables' => MessageTemplate::VARIABLES,
            'template_types' => [
                MessageTemplate::TYPE_TICKET_ONLY => 'Ticket Only',
                MessageTemplate::TYPE_TICKET_WITH_AUDIO => 'Ticket with Audio Guide',
            ],
        ]);
    }

    /**
     * Get templates grouped by language for the wizard
     * GET /api/templates/languages
     */
    public function getLanguages(): JsonResponse
    {
        $languages = MessageTemplate::getAvailableLanguages();

        return response()->json([
            'languages' => $languages,
        ]);
    }

    /**
     * Get template for a specific language and type
     * GET /api/templates/by-language-type
     */
    public function getByLanguageAndType(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'language' => 'required|string|max:10',
            'template_type' => 'required|string',
            'channel' => 'sometimes|string',
        ]);

        $template = MessageTemplate::getByLanguageAndType(
            $validated['language'],
            $validated['template_type'],
            $validated['channel'] ?? 'email'
        );

        if (!$template) {
            return response()->json([
                'error' => 'Template not found',
            ], 404);
        }

        return response()->json([
            'template' => $template,
        ]);
    }

    /**
     * Get a single template
     * GET /api/admin/templates/{id}
     */
    public function show(int $id): JsonResponse
    {
        $template = MessageTemplate::findOrFail($id);

        return response()->json([
            'template' => $template,
            'variables' => MessageTemplate::VARIABLES,
        ]);
    }

    /**
     * Create a new template
     * POST /api/admin/templates
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'channel' => ['required', Rule::in(MessageTemplate::CHANNELS)],
            'language' => 'required|string|max:10',
            'language_name' => 'sometimes|string|max:50',
            'language_flag' => 'sometimes|string|max:10',
            'template_type' => ['sometimes', Rule::in([MessageTemplate::TYPE_TICKET_ONLY, MessageTemplate::TYPE_TICKET_WITH_AUDIO])],
            'subject' => 'nullable|string|max:255',
            'content' => 'required|string',
            'is_default' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer',
        ]);

        // Generate slug
        $templateType = $validated['template_type'] ?? MessageTemplate::TYPE_TICKET_ONLY;
        $baseSlug = Str::slug($validated['name'] . '-' . $validated['channel'] . '-' . $validated['language'] . '-' . $templateType);
        $slug = $baseSlug;
        $counter = 1;

        while (MessageTemplate::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        // Get language info from constants if not provided
        $langInfo = MessageTemplate::LANGUAGES[$validated['language']] ?? null;

        // If setting as default, unset other defaults
        if ($validated['is_default'] ?? false) {
            MessageTemplate::where('channel', $validated['channel'])
                ->where('language', $validated['language'])
                ->where('template_type', $templateType)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $template = MessageTemplate::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'channel' => $validated['channel'],
            'language' => $validated['language'],
            'language_name' => $validated['language_name'] ?? $langInfo['name'] ?? null,
            'language_flag' => $validated['language_flag'] ?? $langInfo['flag'] ?? null,
            'template_type' => $templateType,
            'subject' => $validated['subject'] ?? null,
            'content' => $validated['content'],
            'is_default' => $validated['is_default'] ?? false,
            'is_active' => $validated['is_active'] ?? true,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'template' => $template,
        ], 201);
    }

    /**
     * Update a template
     * PUT /api/admin/templates/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $template = MessageTemplate::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'language_name' => 'sometimes|string|max:50',
            'language_flag' => 'sometimes|string|max:10',
            'template_type' => ['sometimes', Rule::in([MessageTemplate::TYPE_TICKET_ONLY, MessageTemplate::TYPE_TICKET_WITH_AUDIO])],
            'subject' => 'nullable|string|max:255',
            'content' => 'sometimes|string',
            'is_default' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer',
        ]);

        // If setting as default, unset other defaults
        if (($validated['is_default'] ?? false) && !$template->is_default) {
            MessageTemplate::where('channel', $template->channel)
                ->where('language', $template->language)
                ->where('template_type', $template->template_type)
                ->where('is_default', true)
                ->where('id', '!=', $template->id)
                ->update(['is_default' => false]);
        }

        $template->update($validated);

        return response()->json([
            'success' => true,
            'template' => $template->fresh(),
        ]);
    }

    /**
     * Delete a template
     * DELETE /api/admin/templates/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $template = MessageTemplate::findOrFail($id);

        // Check if template is in use
        if ($template->messages()->count() > 0) {
            return response()->json([
                'success' => false,
                'error' => 'Cannot delete template that is in use by messages',
            ], 422);
        }

        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Template deleted',
        ]);
    }

    /**
     * Preview a template with sample data
     * POST /api/admin/templates/{id}/preview
     */
    public function preview(Request $request, int $id): JsonResponse
    {
        $template = MessageTemplate::findOrFail($id);

        // Use sample data or provided variables
        $variables = $request->input('variables', [
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'tour_date' => 'January 30, 2026',
            'tour_time' => '10:00 AM',
            'product_name' => 'Uffizi Gallery Timed Entry Tickets',
            'pax' => '2',
            'reference_number' => 'UFF-12345',
            'audio_guide_url' => 'https://pg.unlockmy.app/abc123',
            'audio_guide_username' => 'TKE-000392',
            'audio_guide_password' => '52628',
        ]);

        return response()->json([
            'subject' => $template->renderSubject($variables),
            'content' => $template->render($variables),
            'variables_used' => $variables,
        ]);
    }

    /**
     * Duplicate a template
     * POST /api/admin/templates/{id}/duplicate
     */
    public function duplicate(Request $request, int $id): JsonResponse
    {
        $template = MessageTemplate::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'language' => ['sometimes', Rule::in(array_keys(MessageTemplate::LANGUAGES))],
        ]);

        $newName = $validated['name'] ?? $template->name . ' (Copy)';
        $newLanguage = $validated['language'] ?? $template->language;

        // Generate new slug
        $baseSlug = Str::slug($newName . '-' . $template->channel . '-' . $newLanguage);
        $slug = $baseSlug;
        $counter = 1;

        while (MessageTemplate::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        $newTemplate = MessageTemplate::create([
            'name' => $newName,
            'slug' => $slug,
            'channel' => $template->channel,
            'language' => $newLanguage,
            'subject' => $template->subject,
            'content' => $template->content,
            'is_default' => false,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'template' => $newTemplate,
        ], 201);
    }
}
